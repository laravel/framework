<?php

namespace Illuminate\Support;

use Illuminate\Filesystem\Filesystem;
use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class ConfigWriter
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new config writer instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Write an env() call into a PHP config file at the given key path.
     *
     * @param  string  $filePath
     * @param  array  $keySegments
     * @param  string  $envVariable
     * @param  string  $default
     * @return void
     */
    public function write(string $filePath, array $keySegments, string $envVariable, string $default = ''): void
    {
        if ($this->files->exists($filePath)) {
            $this->updateExistingFile($filePath, $keySegments, $envVariable, $default);
        } else {
            $this->createNewFile($filePath, $keySegments, $envVariable, $default);
        }
    }

    /**
     * Update an existing config file using format-preserving printing.
     *
     * @param  string  $filePath
     * @param  array  $keySegments
     * @param  string  $envVariable
     * @param  string  $default
     * @return void
     */
    protected function updateExistingFile(string $filePath, array $keySegments, string $envVariable, string $default): void
    {
        $code = $this->files->get($filePath);

        $parser = $this->createParser();
        $oldStmts = $parser->parse($code);
        $oldTokens = $parser->getTokens();

        $traverser = new NodeTraverser;
        $traverser->addVisitor(new CloningVisitor);
        $newStmts = $traverser->traverse($oldStmts);

        $returnArray = $this->findReturnArray($newStmts);

        if ($returnArray === null) {
            return;
        }

        $targetArray = $this->findOrCreateNestedArray($returnArray, array_slice($keySegments, 0, -1));
        $this->setValueInArray($targetArray, end($keySegments), $this->buildEnvCall($envVariable, $default));

        $printer = new Standard;
        $newCode = $printer->printFormatPreserving($newStmts, $oldStmts, $oldTokens);
        $newCode = preg_replace('/^\s*\/\* __CONFIGWRITER__ \*\/\n/m', '', $newCode);

        $this->files->put($filePath, $newCode);
    }

    /**
     * Create a new config file with the given key path and env() call.
     *
     * @param  string  $filePath
     * @param  array  $keySegments
     * @param  string  $envVariable
     * @param  string  $default
     * @return void
     */
    protected function createNewFile(string $filePath, array $keySegments, string $envVariable, string $default): void
    {
        $printer = new Standard(['shortArraySyntax' => true]);

        $envCall = $printer->prettyPrintExpr($this->buildEnvCall($envVariable, $default));

        $code = $this->buildNestedArrayString($keySegments, $envCall);

        $directory = dirname($filePath);

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->put($filePath, $code);
    }

    /**
     * Build an env() FuncCall node.
     *
     * @param  string  $envVariable
     * @param  string  $default
     * @return \PhpParser\Node\Expr\FuncCall
     */
    protected function buildEnvCall(string $envVariable, string $default): FuncCall
    {
        $args = [new Arg(new String_($envVariable))];

        if ($default !== '') {
            $defaultNode = match (true) {
                strtolower($default) === 'null' => new Node\Expr\ConstFetch(new Name('null')),
                strtolower($default) === 'true' => new Node\Expr\ConstFetch(new Name('true')),
                strtolower($default) === 'false' => new Node\Expr\ConstFetch(new Name('false')),
                is_numeric($default) => str_contains($default, '.')
                    ? new Node\Scalar\Float_((float) $default)
                    : new Node\Scalar\Int_((int) $default),
                default => new String_($default),
            };

            $args[] = new Arg($defaultNode);
        }

        return new FuncCall(new Name('env'), $args);
    }

    /**
     * Find the return statement's array in the AST.
     *
     * @param  array  $stmts
     * @return \PhpParser\Node\Expr\Array_|null
     */
    protected function findReturnArray(array $stmts): ?Array_
    {
        foreach ($stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Return_ && $stmt->expr instanceof Array_) {
                return $stmt->expr;
            }
        }

        return null;
    }

    /**
     * Navigate or create nested arrays for the given key segments.
     *
     * @param  \PhpParser\Node\Expr\Array_  $array
     * @param  array  $segments
     * @return \PhpParser\Node\Expr\Array_
     */
    protected function findOrCreateNestedArray(Array_ $array, array $segments): Array_
    {
        foreach ($segments as $segment) {
            $found = false;

            foreach ($array->items as $item) {
                if ($item instanceof ArrayItem
                    && $item->key instanceof String_
                    && $item->key->value === $segment
                ) {
                    if ($item->value instanceof Array_) {
                        $array = $item->value;
                        $found = true;
                        break;
                    }
                }
            }

            if (! $found) {
                $newArray = new Array_([], ['kind' => Array_::KIND_SHORT]);
                $newItem = new ArrayItem($newArray, new String_($segment));
                $newItem->setAttribute('comments', [new Comment('/* __CONFIGWRITER__ */')]);
                $array->items[] = $newItem;
                $array = $newArray;
            }
        }

        return $array;
    }

    /**
     * Set or replace a value in an Array_ node by key.
     *
     * @param  \PhpParser\Node\Expr\Array_  $array
     * @param  string  $key
     * @param  \PhpParser\Node\Expr  $value
     * @return void
     */
    protected function setValueInArray(Array_ $array, string $key, Node\Expr $value): void
    {
        foreach ($array->items as $item) {
            if ($item instanceof ArrayItem
                && $item->key instanceof String_
                && $item->key->value === $key
            ) {
                $item->value = $value;

                return;
            }
        }

        $newItem = new ArrayItem($value, new String_($key));
        $newItem->setAttribute('comments', [new Comment('/* __CONFIGWRITER__ */')]);
        $array->items[] = $newItem;
    }

    /**
     * Build a properly indented nested array string for a new config file.
     *
     * @param  array  $keySegments
     * @param  string  $value
     * @param  int  $depth
     * @return string
     */
    protected function buildNestedArrayString(array $keySegments, string $value, int $depth = 1): string
    {
        $indent = str_repeat('    ', $depth);
        $closingIndent = str_repeat('    ', $depth - 1);
        $key = array_shift($keySegments);

        if (empty($keySegments)) {
            $inner = "{$indent}'{$key}' => {$value},";
        } else {
            $inner = "{$indent}'{$key}' => ".$this->buildNestedArrayString($keySegments, $value, $depth + 1).',';
        }

        $array = "[\n{$inner}\n{$closingIndent}]";

        if ($depth === 1) {
            return "<?php\n\nreturn {$array};\n";
        }

        return $array;
    }

    /**
     * Create a PHP parser instance.
     *
     * @return \PhpParser\Parser
     */
    protected function createParser(): Parser
    {
        return (new ParserFactory)->createForNewestSupportedVersion();
    }
}
