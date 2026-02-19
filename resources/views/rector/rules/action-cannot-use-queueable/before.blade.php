@verbatim
use Illuminate\Foundation\Queue\Queueable;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class Ship implements Action
{
    use AsAction;
    use Queueable;

    public function handle(): void
    {
        // Implementation
    }
}
@endverbatim
