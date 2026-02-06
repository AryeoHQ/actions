<?php

declare(strict_types=1);

namespace Tests\Tooling\Actions\Rector\Rules;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tooling\Actions\Rector\Rules\ShouldQueueMustImplementAction;

#[CoversClass(ShouldQueueMustImplementAction::class)]
class ShouldQueueMustImplementActionTest extends TestCase
{
    private ShouldQueueMustImplementAction $rule;

    private ParserFactory|Parser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = app(ShouldQueueMustImplementAction::class);
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
    }

    #[Test]
    public function adds_action_when_should_queue_is_implemented(): void
    {
        $code = <<<'PHP'
<?php

namespace Tests\Fixtures;

use Illuminate\Contracts\Queue\ShouldQueue;

class TestJob implements ShouldQueue
{
    public function handle(): void
    {
        // Logic here
    }
}
PHP;

        $nodes = $this->parser->parse($code);
        $classNode = $this->getClassNode($nodes);

        $this->assertNotNull($classNode, 'Should find a class node');

        $result = $this->rule->refactor($classNode);

        $this->assertInstanceOf(Class_::class, $result);
        $this->assertGreaterThan(1, count($result->implements));
    }

    #[Test]
    public function does_not_modify_complete_action(): void
    {
        $code = <<<'PHP'
<?php

namespace Tests\Fixtures;

use Illuminate\Contracts\Queue\ShouldQueue;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class TestAction implements Action, ShouldQueue
{
    use AsAction;

    public function handle(): void
    {
        // Logic here
    }
}
PHP;

        $nodes = $this->parser->parse($code);
        $classNode = $this->getClassNode($nodes);

        $this->assertNotNull($classNode, 'Should find a class node');

        $result = $this->rule->refactor($classNode);

        $this->assertNull($result);
    }

    #[Test]
    public function does_not_modify_non_should_queue_class(): void
    {
        $code = <<<'PHP'
<?php

namespace Tests\Fixtures;

class RegularClass
{
    public function handle(): void
    {
        // Logic here
    }
}
PHP;

        $nodes = $this->parser->parse($code);
        $classNode = $this->getClassNode($nodes);

        $this->assertNotNull($classNode, 'Should find a class node');

        $result = $this->rule->refactor($classNode);

        $this->assertNull($result);
    }

    /**
     * @param  array<\PhpParser\Node\Stmt>  $nodes
     */
    private function getClassNode(array $nodes): null|Class_
    {
        foreach ($nodes as $node) {
            if ($node instanceof \PhpParser\Node\Stmt\Namespace_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Class_) {
                        return $stmt;
                    }
                }
            }
        }

        return null;
    }
}
