<?php

namespace Illuminate\Mail\Transport;

use Swift_Mime_Message;
use Illuminate\Filesystem\Filesystem;

class PreviewTransport extends Transport
{
    /**
     * The Filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Get the preview path.
     *
     * @var string
     */
    protected $previewPath;

    /**
     * Create a new preview transport instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $previewPath
     * @return void
     */
    public function __construct(Filesystem $files, $previewPath)
    {
        $this->files = $files;
        $this->previewPath = $previewPath;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $this->createEmailPreviewDirectory();

        $this->cleanOldPreviews();

        $this->files->put(
            $this->getEmailPreviewPath($message),
            $this->getEmailPreviewContent($message)
        );
    }

    /**
     * Get the path to the email preview file.
     *
     * @param  \Swift_Mime_Message $message
     * @return string
     */
    protected function getEmailPreviewPath(Swift_Mime_Message $message)
    {
        $to = str_replace('@', '_at_', array_keys($message->getTo())[0]);

        $subject = $message->getSubject();

        return $this->previewPath.'/'.str_slug($to.'_'.$subject, '_').'.html';
    }

    /**
     * Get the content of the email preview file.
     *
     * @param  \Swift_Mime_Message $message
     * @return string
     */
    protected function getEmailPreviewContent(Swift_Mime_Message $message)
    {
        $fromEmail = $message->getFrom() ? array_keys($message->getFrom())[0] : '';

        $toEmail = $message->getTo() ? array_keys($message->getTo())[0] : '';

        $messageInfo = sprintf('<!--From:%s, to:%s, subject:%s-->', $fromEmail, $toEmail, $message->getSubject());

        return $messageInfo.$message->getBody();
    }

    /**
     * Create the preview directory if necessary.
     *
     * @return void
     */
    protected function createEmailPreviewDirectory()
    {
        if (! $this->files->exists($this->previewPath)) {
            $this->files->makeDirectory($this->previewPath);

            $this->files->put($this->previewPath.'/.gitignore', "*\n!.gitignore");
        }
    }

    /**
     * Delete all old previews.
     *
     * @return void
     */
    private function cleanOldPreviews()
    {
        $oldPreviews = array_filter($this->files->files($this->previewPath), function ($file) {
            return time() - $this->files->lastModified($file) > 60;
        });

        if ($oldPreviews) {
            $this->files->delete($oldPreviews);
        }
    }
}
