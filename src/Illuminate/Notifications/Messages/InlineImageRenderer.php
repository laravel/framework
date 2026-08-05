<?php

namespace Illuminate\Notifications\Messages;

use Illuminate\Mail\Message as MailMessage;
use LogicException;

class InlineImageRenderer
{
    /**
     * Resolve inline images in the notification's line data.
     *
     * @param  array  $data
     * @param  \Illuminate\Mail\Message|null  $mailMessage
     * @param  bool  $text
     * @return array
     *
     * @throws \LogicException
     */
    public static function render(array $data, $mailMessage = null, $text = false)
    {
        foreach (['introLines', 'outroLines'] as $key) {
            if (! isset($data[$key])) {
                continue;
            }

            $data[$key] = array_map(function ($line) use ($mailMessage, $text) {
                if ($line instanceof InlineImage) {
                    return $text
                        ? $line->toText()
                        : static::renderHtml($line, $mailMessage);
                }

                if ($line instanceof InlineImageLine) {
                    return $text
                        ? $line->toText()
                        : static::renderHtml($line, $mailMessage);
                }

                return $line;
            }, $data[$key]);
        }

        return $data;
    }

    /**
     * Render an inline image line for an HTML mail message.
     *
     * @param  \Illuminate\Notifications\Messages\InlineImage|\Illuminate\Notifications\Messages\InlineImageLine  $line
     * @param  \Illuminate\Mail\Message|null  $mailMessage
     * @return \Illuminate\Support\HtmlString
     *
     * @throws \LogicException
     */
    protected static function renderHtml($line, $mailMessage)
    {
        if (! $mailMessage instanceof MailMessage) {
            throw new LogicException('Inline notification images can only be rendered while sending a mail message.');
        }

        return $line->toHtml($mailMessage);
    }
}
