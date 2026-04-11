<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class UpdateRouter
{
    public function __construct(
        private StartHandler $startHandler,
        private CallbackHandler $callbackHandler,
        private MessageHandler $messageHandler,
    ) {
    }

    public function route(array $update): void
    {
        if (isset($update['message'])) {
            $this->startHandler->handle($update);
            $this->messageHandler->handle($update);
        }

        if (isset($update['callback_query'])) {
            $this->callbackHandler->handle($update);
        }
    }
}
