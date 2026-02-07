<?php

declare(strict_types=1);

namespace Support\Actions\Concerns {
    use Illuminate\Foundation\Bus\Dispatchable;
    use Illuminate\Queue\InteractsWithQueue;
    use Illuminate\Queue\SerializesModels;

    trait AsAction
    {
        use Dispatchable;
        use InteractsWithQueue;
        use SerializesModels;

        public static function make(mixed ...$arguments): static
        {
            return new static(...$arguments);
        }
    }
}
