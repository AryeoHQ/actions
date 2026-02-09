<?php

declare(strict_types=1);

namespace Tests\Tooling\Actions\Rector\Rules;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tooling\Actions\Rector\Rules\ActionCannotUseDispatchable;

#[CoversClass(ActionCannotUseDispatchable::class)]
class ActionCannotUseDispatchableTest extends TestCase
{
    private ActionCannotUseDispatchable $rule;

    private ParserFactory|Parser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = app(ActionCannotUseDispatchable::class);
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
    }

    private function getFixture(string $filename): string
    {
        return file_get_contents(__DIR__.'/../../../../Fixtures/Tooling/'.$filename);
    }

    #[Test]
    public function removes_dispatchable_trait_from_action(): void
    {
        $code = $this->getFixture('ActionWithDispatchable.php');

        $nodes = $this->parser->parse($code);
        $classNode = $this->getClassNode($nodes);

        $this->assertNotNull($classNode, 'Should find a class node');

        $result = $this->rule->refactor($classNode);

        $this->assertInstanceOf(Class_::class, $result);

        // Verify Dispatchable trait was removed
        $hasDispatchable = false;
        foreach ($result->stmts as $stmt) {
            if ($stmt instanceof \PhpParser\Node\Stmt\TraitUse) {
                foreach ($stmt->traits as $trait) {
                    if ($trait->toString() === 'Dispatchable') {
                        $hasDispatchable = true;
                    }
                }
            }
        }

        $this->assertFalse($hasDispatchable, 'Dispatchable trait should be removed');
    }

    #[Test]
    public function does_not_modify_action_without_dispatchable(): void
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
