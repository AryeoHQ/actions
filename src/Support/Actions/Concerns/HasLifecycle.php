<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use Support\Actions\Bus\Invocation;

trait HasLifecycle
{
    public null|Invocation $invokedVia = null;

    public function prepareFor(Invocation $via): static
    {
        $this->clearJob();

        $this->invokedVia = $via;

        when(
            method_exists($this, 'prepare'), // @phpstan-ignore function.impossibleType, function.alreadyNarrowedType
            fn () => call_user_func([$this, 'prepare']) // @phpstan-ignore argument.type
        );

        return $this;
    }
}
