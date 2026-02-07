<?php

declare(strict_types=1);

namespace Support\Actions\Concerns {
    use Illuminate\Foundation\Queue\Queueable;
    use Illuminate\Queue\InteractsWithQueue;
    use Illuminate\Queue\SerializesModels;

    trait AsAction
    {
        use Queueable;
        use InteractsWithQueue;
        use SerializesModels;

        public static function make(mixed ...$arguments): static
        {
            return new static(...$arguments);
        }
    }
}
