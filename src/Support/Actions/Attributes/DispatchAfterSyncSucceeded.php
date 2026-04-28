<?php

declare(strict_types=1);

namespace Support\Actions\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class DispatchAfterSyncSucceeded {}
