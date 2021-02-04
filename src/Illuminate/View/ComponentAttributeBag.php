<?php

namespace Illuminate\View;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use IteratorAggregate;

/**
 * @method static accept(mixed|array|string $value)
 * @method static acceptCharset(mixed|array|string $value)
 * @method static accesskey(mixed|array|string $value)
 * @method static action(mixed|array|string $value)
 * @method static alt(mixed|array|string $value)
 * @method static autocomplete(mixed|array|string $value)
 * @method static charset(mixed|array|string $value)
 * @method static class(mixed|array|string $classList)
 * @method static cite(mixed|array|string $value)
 * @method static cols(mixed|array|string $value)
 * @method static colspan(mixed|array|string $value)
 * @method static content(mixed|array|string $value)
 * @method static contenteditable(mixed|array|string $value)
 * @method static coords(mixed|array|string $value)
 * @method static data(mixed|array|string $value)
 * @method static dataAttr(string $key, mixed|array|string $values)
 * @method static datetime(mixed|array|string $value)
 * @method static dir(mixed|array|string $value)
 * @method static dirname(mixed|array|string $value)
 * @method static download(mixed|array|string $value)
 * @method static draggable(mixed|array|string $value)
 * @method static enctype(mixed|array|string $value)
 * @method static for(mixed|array|string $value)
 * @method static form(mixed|array|string $value)
 * @method static formaction(mixed|array|string $value)
 * @method static headers(mixed|array|string $value)
 * @method static height(mixed|array|string $value)
 * @method static high(mixed|array|string $value)
 * @method static href(mixed|array|string $value)
 * @method static hreflang(mixed|array|string $value)
 * @method static httpEquiv(mixed|array|string $value)
 * @method static id(mixed|array|string $value)
 * @method static kind(mixed|array|string $value)
 * @method static label(mixed|array|string $value)
 * @method static list(mixed|array|string $value)
 * @method static low(mixed|array|string $value)
 * @method static max(mixed|array|string $value)
 * @method static maxlength(mixed|array|string $value)
 * @method static media(mixed|array|string $value)
 * @method static method(mixed|array|string $value)
 * @method static min(mixed|array|string $value)
 * @method static name(mixed|array|string $value)
 * @method static onabort(mixed|array|string $value)
 * @method static onafterprint(mixed|array|string $value)
 * @method static onbeforeprint(mixed|array|string $value)
 * @method static onbeforeunload(mixed|array|string $value)
 * @method static onblur(mixed|array|string $value)
 * @method static oncanplay(mixed|array|string $value)
 * @method static oncanplaythrough(mixed|array|string $value)
 * @method static onchange(mixed|array|string $value)
 * @method static onclick(mixed|array|string $value)
 * @method static oncontextmenu(mixed|array|string $value)
 * @method static oncopy(mixed|array|string $value)
 * @method static oncuechange(mixed|array|string $value)
 * @method static oncut(mixed|array|string $value)
 * @method static ondblclick(mixed|array|string $value)
 * @method static ondrag(mixed|array|string $value)
 * @method static ondragend(mixed|array|string $value)
 * @method static ondragenter(mixed|array|string $value)
 * @method static ondragleave(mixed|array|string $value)
 * @method static ondragover(mixed|array|string $value)
 * @method static ondragstart(mixed|array|string $value)
 * @method static ondrop(mixed|array|string $value)
 * @method static ondurationchange(mixed|array|string $value)
 * @method static onemptied(mixed|array|string $value)
 * @method static onended(mixed|array|string $value)
 * @method static onerror(mixed|array|string $value)
 * @method static onfocus(mixed|array|string $value)
 * @method static onhashchange(mixed|array|string $value)
 * @method static oninput(mixed|array|string $value)
 * @method static oninvalid(mixed|array|string $value)
 * @method static onkeydown(mixed|array|string $value)
 * @method static onkeypress(mixed|array|string $value)
 * @method static onkeyup(mixed|array|string $value)
 * @method static onload(mixed|array|string $value)
 * @method static onloadeddata(mixed|array|string $value)
 * @method static onloadedmetadata(mixed|array|string $value)
 * @method static onloadstart(mixed|array|string $value)
 * @method static onmousedown(mixed|array|string $value)
 * @method static onmousemove(mixed|array|string $value)
 * @method static onmouseout(mixed|array|string $value)
 * @method static onmouseover(mixed|array|string $value)
 * @method static onmouseup(mixed|array|string $value)
 * @method static onmousewheel(mixed|array|string $value)
 * @method static onoffline(mixed|array|string $value)
 * @method static ononline(mixed|array|string $value)
 * @method static onpagehide(mixed|array|string $value)
 * @method static onpageshow(mixed|array|string $value)
 * @method static onpaste(mixed|array|string $value)
 * @method static onpause(mixed|array|string $value)
 * @method static onplay(mixed|array|string $value)
 * @method static onplaying(mixed|array|string $value)
 * @method static onpopstate(mixed|array|string $value)
 * @method static onprogress(mixed|array|string $value)
 * @method static onratechange(mixed|array|string $value)
 * @method static onreset(mixed|array|string $value)
 * @method static onresize(mixed|array|string $value)
 * @method static onscroll(mixed|array|string $value)
 * @method static onsearch(mixed|array|string $value)
 * @method static onseeked(mixed|array|string $value)
 * @method static onseeking(mixed|array|string $value)
 * @method static onselect(mixed|array|string $value)
 * @method static onstalled(mixed|array|string $value)
 * @method static onstorage(mixed|array|string $value)
 * @method static onsubmit(mixed|array|string $value)
 * @method static onsuspend(mixed|array|string $value)
 * @method static ontimeupdate(mixed|array|string $value)
 * @method static ontoggle(mixed|array|string $value)
 * @method static onunload(mixed|array|string $value)
 * @method static onvolumechange(mixed|array|string $value)
 * @method static onwaiting(mixed|array|string $value)
 * @method static onwheel(mixed|array|string $value)
 * @method static optimum(mixed|array|string $value)
 * @method static pattern(mixed|array|string $value)
 * @method static placeholder(mixed|array|string $value)
 * @method static poster(mixed|array|string $value)
 * @method static preload(mixed|array|string $value)
 * @method static rel(mixed|array|string $value)
 * @method static rows(mixed|array|string $value)
 * @method static rowspan(mixed|array|string $value)
 * @method static scope(mixed|array|string $value)
 * @method static shape(mixed|array|string $value)
 * @method static size(mixed|array|string $value)
 * @method static sizes(mixed|array|string $value)
 * @method static span(mixed|array|string $value)
 * @method static spellcheck(mixed|array|string $value)
 * @method static src(mixed|array|string $value)
 * @method static srcdoc(mixed|array|string $value)
 * @method static srclang(mixed|array|string $value)
 * @method static srcset(mixed|array|string $value)
 * @method static start(mixed|array|string $value)
 * @method static step(mixed|array|string $value)
 * @method static style(mixed|array|string $styleList)
 * @method static tabindex(mixed|array|string $value)
 * @method static target(mixed|array|string $value)
 * @method static title(mixed|array|string $value)
 * @method static translate(mixed|array|string $value)
 * @method static type(mixed|array|string $value)
 * @method static usemap(mixed|array|string $value)
 * @method static value(mixed|array|string $value)
 * @method static width(mixed|array|string $value)
 * @method static wrap(mixed|array|string $value)
 */
class ComponentAttributeBag implements ArrayAccess, Htmlable, IteratorAggregate
{
    use Macroable;

    /**
     * The raw array of attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * List of magic attribute methods.
     *
     * @var array
     */
    protected $attributeMethods = [
        'accept',
        'acceptCharset',
        'accesskey',
        'action',
        'alt',
        'autocomplete',
        'charset',
        'class',
        'cite',
        'cols',
        'colspan',
        'content',
        'contenteditable',
        'coords',
        'data',
        'dataAttr',
        'datetime',
        'dir',
        'dirname',
        'download',
        'draggable',
        'enctype',
        'for',
        'form',
        'formaction',
        'headers',
        'height',
        'high',
        'href',
        'hreflang',
        'httpEquiv',
        'id',
        'kind',
        'label',
        'list',
        'low',
        'max',
        'maxlength',
        'media',
        'method',
        'min',
        'name',
        'onabort',
        'onafterprint',
        'onbeforeprint',
        'onbeforeunload',
        'onblur',
        'oncanplay',
        'oncanplaythrough',
        'onchange',
        'onclick',
        'oncontextmenu',
        'oncopy',
        'oncuechange',
        'oncut',
        'ondblclick',
        'ondrag',
        'ondragend',
        'ondragenter',
        'ondragleave',
        'ondragover',
        'ondragstart',
        'ondrop',
        'ondurationchange',
        'onemptied',
        'onended',
        'onerror',
        'onfocus',
        'onhashchange',
        'oninput',
        'oninvalid',
        'onkeydown',
        'onkeypress',
        'onkeyup',
        'onload',
        'onloadeddata',
        'onloadedmetadata',
        'onloadstart',
        'onmousedown',
        'onmousemove',
        'onmouseout',
        'onmouseover',
        'onmouseup',
        'onmousewheel',
        'onoffline',
        'ononline',
        'onpagehide',
        'onpageshow',
        'onpaste',
        'onpause',
        'onplay',
        'onplaying',
        'onpopstate',
        'onprogress',
        'onratechange',
        'onreset',
        'onresize',
        'onscroll',
        'onsearch',
        'onseeked',
        'onseeking',
        'onselect',
        'onstalled',
        'onstorage',
        'onsubmit',
        'onsuspend',
        'ontimeupdate',
        'ontoggle',
        'onunload',
        'onvolumechange',
        'onwaiting',
        'onwheel',
        'optimum',
        'pattern',
        'placeholder',
        'poster',
        'preload',
        'rel',
        'rows',
        'rowspan',
        'scope',
        'shape',
        'size',
        'sizes',
        'span',
        'spellcheck',
        'src',
        'srcdoc',
        'srclang',
        'srcset',
        'start',
        'step',
        'style',
        'tabindex',
        'target',
        'title',
        'translate',
        'type',
        'usemap',
        'value',
        'width',
        'wrap',
    ];

    /**
     * List of attributes that have not yet been processed.
     *
     * @var array
     */
    protected $attributesBag = [];

    /**
     * Create a new component attribute bag instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Get the first attribute's value.
     *
     * @param  mixed  $default
     * @return mixed
     */
    public function first($default = null)
    {
        return $this->getIterator()->current() ?? value($default);
    }

    /**
     * Get a given attribute from the attribute array.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->attributes[$key] ?? value($default);
    }

    /**
     * Determine if a given attribute exists in the attribute array.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * Only include the given attribute from the attribute array.
     *
     * @param  mixed  $keys
     * @return static
     */
    public function only($keys)
    {
        if (is_null($keys)) {
            $values = $this->attributes;
        } else {
            $keys = Arr::wrap($keys);

            $values = Arr::only($this->attributes, $keys);
        }

        return new static($values);
    }

    /**
     * Exclude the given attribute from the attribute array.
     *
     * @param  mixed|array  $keys
     * @return static
     */
    public function except($keys)
    {
        if (is_null($keys)) {
            $values = $this->attributes;
        } else {
            $keys = Arr::wrap($keys);

            $values = Arr::except($this->attributes, $keys);
        }

        return new static($values);
    }

    /**
     * Filter the attributes, returning a bag of attributes that pass the filter.
     *
     * @param  callable  $callback
     * @return static
     */
    public function filter($callback)
    {
        return new static(collect($this->attributes)->filter($callback)->all());
    }

    /**
     * Return a bag of attributes that have keys starting with the given value / pattern.
     *
     * @param  string  $string
     * @return static
     */
    public function whereStartsWith($string)
    {
        return $this->filter(function ($value, $key) use ($string) {
            return Str::startsWith($key, $string);
        });
    }

    /**
     * Return a bag of attributes with keys that do not start with the given value / pattern.
     *
     * @param  string  $string
     * @return static
     */
    public function whereDoesntStartWith($string)
    {
        return $this->filter(function ($value, $key) use ($string) {
            return ! Str::startsWith($key, $string);
        });
    }

    /**
     * Return a bag of attributes that have keys starting with the given value / pattern.
     *
     * @param  string  $string
     * @return static
     */
    public function thatStartWith($string)
    {
        return $this->whereStartsWith($string);
    }

    /**
     * Exclude the given attribute from the attribute array.
     *
     * @param  mixed|array  $keys
     * @return static
     */
    public function exceptProps($keys)
    {
        $props = [];

        foreach ($keys as $key => $defaultValue) {
            $key = is_numeric($key) ? $defaultValue : $key;

            $props[] = $key;
            $props[] = Str::kebab($key);
        }

        return $this->except($props);
    }

    /**
     * Conditionally set attributes into the attribute bag.
     *
     * @param string $method
     * @param array  $arguments
     * @return static
     */
    public function makeAttribute($method, $arguments)
    {
        $key = $method;
        $mergeValues = false;
        $mergeSeparator = ' ';
        $strKebab = true;

        if (count($arguments) === 1) {
            $list = Arr::first($arguments);
        } else {
            [$key, $list] = $arguments;
        }

        $list = Arr::wrap($list);

        switch ($method) {
            case 'accept':
                $mergeValues = true;
                $mergeSeparator = '|';
                break;
            case 'class':
                $mergeValues = true;
                break;
            case 'dataAttr':
                $key = 'data-'.$key;
                $strKebab = false;
                break;
            case 'style':
                $mergeValues = true;
                $mergeSeparator = ';';
                break;
        }

        if ($strKebab) {
            $key = Str::kebab($key);
        }

        if (! $mergeValues && Arr::exists($this->attributesBag, $key)) {
            return $this;
        }

        $result = $mergeValues ? [] : null;

        foreach ($list as $value => $constraint) {
            if (is_numeric($value)) {
                if ($mergeValues) {
                    $result[] = $constraint;
                } else {
                    $result = $constraint;
                    break;
                }
            } elseif ($constraint) {
                if ($mergeValues) {
                    $result[] = $value;
                } else {
                    $result = $value;
                    break;
                }
            }
        }

        $this->attributesBag[$key] = $mergeValues ? implode($mergeSeparator, $result) : $result;

        return $this;

    }

    /**
     * Merge additional attributes / values into the attribute bag.
     *
     * @param  array  $attributeDefaults
     * @param  bool  $escape
     * @return static
     */
    public function merge(array $attributeDefaults = [], $escape = true)
    {
        $attributeDefaults = array_merge($this->attributesBag, $attributeDefaults);

        $attributeDefaults = array_map(function ($value) use ($escape) {
            return $this->shouldEscapeAttributeValue($escape, $value)
                        ? e($value)
                        : $value;
        }, $attributeDefaults);

        [$appendableAttributes, $nonAppendableAttributes] = collect($this->attributes)
                    ->partition(function ($value, $key) use ($attributeDefaults) {
                        return $key === 'class' ||
                               (isset($attributeDefaults[$key]) &&
                                $attributeDefaults[$key] instanceof AppendableAttributeValue);
                    });

        $attributes = $appendableAttributes->mapWithKeys(function ($value, $key) use ($attributeDefaults, $escape) {
            $defaultsValue = isset($attributeDefaults[$key]) && $attributeDefaults[$key] instanceof AppendableAttributeValue
                        ? $this->resolveAppendableAttributeDefault($attributeDefaults, $key, $escape)
                        : ($attributeDefaults[$key] ?? '');

            return [$key => implode(' ', array_unique(array_filter([$defaultsValue, $value])))];
        })->merge($nonAppendableAttributes)->all();

        return new static(array_merge($attributeDefaults, $attributes));
    }

    /**
     * Determine if the specific attribute value should be escaped.
     *
     * @param  bool  $escape
     * @param  mixed  $value
     * @return bool
     */
    protected function shouldEscapeAttributeValue($escape, $value)
    {
        if (! $escape) {
            return false;
        }

        return ! is_object($value) &&
               ! is_null($value) &&
               ! is_bool($value);
    }

    /**
     * Create a new appendable attribute value.
     *
     * @param  mixed  $value
     * @return \Illuminate\View\AppendableAttributeValue
     */
    public function prepends($value)
    {
        return new AppendableAttributeValue($value);
    }

    /**
     * Resolve an appendable attribute value default value.
     *
     * @param  array  $attributeDefaults
     * @param  string  $key
     * @param  bool  $escape
     * @return mixed
     */
    protected function resolveAppendableAttributeDefault($attributeDefaults, $key, $escape)
    {
        if ($this->shouldEscapeAttributeValue($escape, $value = $attributeDefaults[$key]->value)) {
            $value = e($value);
        }

        return $value;
    }

    /**
     * Get all of the raw attributes.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set the underlying attributes.
     *
     * @param  array  $attributes
     * @return void
     */
    public function setAttributes(array $attributes)
    {
        if (isset($attributes['attributes']) &&
            $attributes['attributes'] instanceof self) {
            $parentBag = $attributes['attributes'];

            unset($attributes['attributes']);

            $attributes = $parentBag->merge($attributes, $escape = false)->getAttributes();
        }

        $this->attributes = $attributes;
    }

    /**
     * Get content as a string of HTML.
     *
     * @return string
     */
    public function toHtml()
    {
        return (string) $this;
    }

    /**
     * Merge additional attributes / values into the attribute bag.
     *
     * @param  array  $attributeDefaults
     * @return \Illuminate\Support\HtmlString
     */
    public function __invoke(array $attributeDefaults = [])
    {
        return new HtmlString((string) $this->merge($attributeDefaults));
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * Get the value at the given offset.
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Set the value at a given offset.
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->attributes[$offset] = $value;
    }

    /**
     * Remove the value at the given offset.
     *
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->attributes);
    }

    /**
     * Implode the attributes into a single HTML ready string.
     *
     * @return string
     */
    public function __toString()
    {
        $string = '';

        foreach ($this->attributes as $key => $value) {
            if ($value === false || is_null($value)) {
                continue;
            }

            if ($value === true) {
                $value = $key;
            }

            $string .= ' '.$key.'="'.str_replace('"', '\\"', trim($value)).'"';
        }

        return trim($string);
    }

    /**
     * Dynamically set attributes to the component.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \Illuminate\View\ComponentAttributeBag
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (! in_array($method, $this->attributeMethods)) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        } else {
            return $this->makeAttribute($method, $parameters);
        }
    }
}
