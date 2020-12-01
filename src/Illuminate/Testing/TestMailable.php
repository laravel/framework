<?php

namespace Illuminate\Testing;

use Illuminate\Container\Container;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Mail\Mailable;
use Illuminate\Testing\Assert as PHPUnit;

class TestMailable
{
    /**
     * The mailable to delegate to.
     *
     * @var Mailable
     */
    public $baseMailable;

    public $html;

    public $text;

    public function __construct(Mailable $mailable)
    {
        $this->baseMailable = $mailable;

        if (Container::getInstance()->has('mailer')) {
            $this->render();
        }
    }

    public function seeInHtml()
    {
        return function ($needle) {
            PHPUnit::assertStringContainsString(
                $needle,
                $this->html
            );

            return true;
        };
    }

    public function dontSeeInHtml()
    {
        return function ($needle) {
            PHPUnit::assertStringNotContainsString(
                $needle,
                $this->html
            );

            return true;
        };
    }

    public function seeInText()
    {
        return function ($needle) {
            PHPUnit::assertStringContainsString(
                $needle,
                $this->text
            );

            return true;
        };
    }

    public function dontSeeInText()
    {
        return function ($needle) {
            PHPUnit::assertStringNotContainsString(
                $needle,
                $this->text
            );

            return true;
        };
    }

    protected function render()
    {
        [$view, $data] = $this->baseMailable->render();

        if (! is_array($view)) {
            $view = [$view];
        }

        // This array key preference logic is borrowed from Mailer class
        $this->html = $this->renderView($view[0] ?? $view['html'] ?? null, $data);
        $this->text = $this->renderView($view[1] ?? $view['text'] ?? null, $data);

        return $this;
    }

    /**
     * Render the given view.
     *
     * @param  string  $view
     * @param  array  $data
     * @return string
     */
    protected function renderView($view, $data)
    {
        return $view instanceof Htmlable
            ? $view->toHtml()
            : Container::getInstance()->make(Factory::class)->make($view, $data)->render();
    }
}
