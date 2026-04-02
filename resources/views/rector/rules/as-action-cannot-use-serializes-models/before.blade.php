@verbatim
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

trait AsAction
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
}
@endverbatim
