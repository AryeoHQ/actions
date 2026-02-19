@verbatim
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

class Ship implements Action
{
    use AsAction;

    public function handle(): void
    {
        // Implementation
    }
}
@endverbatim
