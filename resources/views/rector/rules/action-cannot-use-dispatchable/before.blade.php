@verbatim
use Illuminate\Foundation\Bus\Dispatchable;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class Ship implements Action
{
    use AsAction;
    use Dispatchable;

    public function handle(): void
    {
        // Implementation
    }
}
@endverbatim
