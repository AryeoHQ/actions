<?php

declare(strict_types=1);

namespace Tests\Tooling\Actions\Rector\Rules;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tooling\Actions\Rector\Rules\ActionMustBeFinal;

#[CoversClass(ActionMustBeFinal::class)]
class ActionMustBeFinalTest extends TestCase
{
    private ActionMustBeFinal $rule;

    private ParserFactory|Parser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = app(ActionMustBeFinal::class);
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
    }

    private function getFixture(string $filename): string
    {
        return file_get_contents(__DIR__.'/../../../../Fixtures/Tooling/'.$filename);
    }

    #[Test]
    public function makes_action_class_final(): void
    {
        $code = $this->getFixture('NotFinalAction.php');

        $nodes = $this->parser->parse($code);
        $classNode = $this->getClassNode($nodes);

        $this->assertNotNull($classNode, 'Should find a class node');
        $this->assertFalse($classNode->isFinal(), 'Class should not be final initially');

        $result = $this->rule->refactor($classNode);

        $this->assertInstanceOf(Class_::class, $result);
        $this->assertTrue($result->isFinal(), 'Action class should be made final');
    }

    #[Test]
    public function does_not_modify_already_final_class(): void
    {
        $code = $this->getFixture('ValidAction.php');

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
