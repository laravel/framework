<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Contracts\NodePackageManager as NodePackageManagerContract;
use Illuminate\Support\NodePackageManager;
use Illuminate\Support\NodePackageManagers\Bun;
use Illuminate\Support\NodePackageManagers\Npm;
use Illuminate\Support\NodePackageManagers\Pnpm;
use Illuminate\Support\NodePackageManagers\Yarn;
use PHPUnit\Framework\TestCase;

class SupportNodePackageManagerTest extends TestCase
{
    public function testNpmRunCommand()
    {
        $npm = new Npm;

        $this->assertSame('npm run dev', $npm->getRunCommand('dev'));
    }

    public function testNpmExecCommand()
    {
        $npm = new Npm;

        $this->assertSame('npx concurrently', $npm->getExecCommand('concurrently'));
    }

    public function testNpmMatches()
    {
        $dir = sys_get_temp_dir().'/npm_test_'.uniqid();
        mkdir($dir);
        touch($dir.'/package-lock.json');

        $original = getcwd();
        chdir($dir);

        try {
            $this->assertTrue(Npm::matches());
        } finally {
            chdir($original);
            unlink($dir.'/package-lock.json');
            rmdir($dir);
        }
    }

    public function testNpmDoesNotMatchWithoutLockFile()
    {
        $dir = sys_get_temp_dir().'/npm_test_'.uniqid();
        mkdir($dir);

        $original = getcwd();
        chdir($dir);

        try {
            $this->assertFalse(Npm::matches());
        } finally {
            chdir($original);
            rmdir($dir);
        }
    }

    public function testYarnRunCommand()
    {
        $yarn = new Yarn;

        $this->assertSame('yarn run dev', $yarn->getRunCommand('dev'));
    }

    public function testYarnExecCommand()
    {
        $yarn = new Yarn;

        $this->assertSame('yarn dlx concurrently', $yarn->getExecCommand('concurrently'));
    }

    public function testYarnMatches()
    {
        $dir = sys_get_temp_dir().'/yarn_test_'.uniqid();
        mkdir($dir);
        touch($dir.'/yarn.lock');

        $original = getcwd();
        chdir($dir);

        try {
            $this->assertTrue(Yarn::matches());
        } finally {
            chdir($original);
            unlink($dir.'/yarn.lock');
            rmdir($dir);
        }
    }

    public function testPnpmRunCommand()
    {
        $pnpm = new Pnpm;

        $this->assertSame('pnpm run dev', $pnpm->getRunCommand('dev'));
    }

    public function testPnpmExecCommand()
    {
        $pnpm = new Pnpm;

        $this->assertSame('pnpm dlx concurrently', $pnpm->getExecCommand('concurrently'));
    }

    public function testPnpmMatches()
    {
        $dir = sys_get_temp_dir().'/pnpm_test_'.uniqid();
        mkdir($dir);
        touch($dir.'/pnpm-lock.yaml');

        $original = getcwd();
        chdir($dir);

        try {
            $this->assertTrue(Pnpm::matches());
        } finally {
            chdir($original);
            unlink($dir.'/pnpm-lock.yaml');
            rmdir($dir);
        }
    }

    public function testBunRunCommand()
    {
        $bun = new Bun;

        $this->assertSame('bun run dev', $bun->getRunCommand('dev'));
    }

    public function testBunExecCommand()
    {
        $bun = new Bun;

        $this->assertSame('bunx concurrently', $bun->getExecCommand('concurrently'));
    }

    public function testBunMatchesWithBunLock()
    {
        $dir = sys_get_temp_dir().'/bun_test_'.uniqid();
        mkdir($dir);
        touch($dir.'/bun.lock');

        $original = getcwd();
        chdir($dir);

        try {
            $this->assertTrue(Bun::matches());
        } finally {
            chdir($original);
            unlink($dir.'/bun.lock');
            rmdir($dir);
        }
    }

    public function testBunMatchesWithBunLockb()
    {
        $dir = sys_get_temp_dir().'/bun_test_'.uniqid();
        mkdir($dir);
        touch($dir.'/bun.lockb');

        $original = getcwd();
        chdir($dir);

        try {
            $this->assertTrue(Bun::matches());
        } finally {
            chdir($original);
            unlink($dir.'/bun.lockb');
            rmdir($dir);
        }
    }

    public function testManagerDelegatesToInjectedPackageManager()
    {
        $mock = new class implements NodePackageManagerContract {
            public static function matches(): bool
            {
                return true;
            }

            public function getRunCommand(string $command): string
            {
                return "custom run {$command}";
            }

            public function getExecCommand(string $command): string
            {
                return "custom exec {$command}";
            }
        };

        $manager = new NodePackageManager($mock);

        $this->assertSame('custom run dev', $manager->getRunCommand('dev'));
        $this->assertSame('custom exec vite', $manager->getExecCommand('vite'));
    }

    public function testManagerDetectsPackageManagerWhenNoneInjected()
    {
        $dir = sys_get_temp_dir().'/detect_test_'.uniqid();
        mkdir($dir);
        touch($dir.'/package-lock.json');

        $original = getcwd();
        chdir($dir);

        try {
            $manager = new NodePackageManager;

            $this->assertSame('npm run dev', $manager->getRunCommand('dev'));
            $this->assertSame('npx vite', $manager->getExecCommand('vite'));
        } finally {
            chdir($original);
            unlink($dir.'/package-lock.json');
            rmdir($dir);
        }
    }

    public function testDetectionPriorityBunOverNpm()
    {
        $dir = sys_get_temp_dir().'/priority_test_'.uniqid();
        mkdir($dir);
        touch($dir.'/bun.lock');
        touch($dir.'/package-lock.json');

        $original = getcwd();
        chdir($dir);

        try {
            $manager = new NodePackageManager;

            $this->assertSame('bun run dev', $manager->getRunCommand('dev'));
        } finally {
            chdir($original);
            unlink($dir.'/bun.lock');
            unlink($dir.'/package-lock.json');
            rmdir($dir);
        }
    }

    public function testDetectionFallsBackToNpm()
    {
        $dir = sys_get_temp_dir().'/fallback_test_'.uniqid();
        mkdir($dir);

        $original = getcwd();
        chdir($dir);

        try {
            $manager = new NodePackageManager;

            $this->assertSame('npm run dev', $manager->getRunCommand('dev'));
        } finally {
            chdir($original);
            rmdir($dir);
        }
    }

    public function testPackageManagerMethodReturnsDetectedManager()
    {
        $dir = sys_get_temp_dir().'/pm_method_test_'.uniqid();
        mkdir($dir);
        touch($dir.'/yarn.lock');

        $original = getcwd();
        chdir($dir);

        try {
            $manager = new NodePackageManager;

            $this->assertInstanceOf(Yarn::class, $manager->packageManager());
        } finally {
            chdir($original);
            unlink($dir.'/yarn.lock');
            rmdir($dir);
        }
    }
}
