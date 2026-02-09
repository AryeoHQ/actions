<?php

declare(strict_types=1);

namespace Tests\Tooling\Actions\Rector\Rules;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tooling\Actions\Rector\Rules\ActionMustDefineHandleMethod;

#[CoversClass(ActionMustDefineHandleMethod::class)]
class ActionMustDefineHandleMethodTest extends TestCase
{
    private ActionMustDefineHandleMethod $rule;

    private ParserFactory|Parser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = app(ActionMustDefineHandleMethod::class);
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
    }

    private function getFixture(string $filename): string
    {
        return file_get_contents(__DIR__.'/../../../../Fixtures/Tooling/'.$filename);
    }

    #[Test]
    public function adds_handle_method_to_action_without_it(): void
    {
        $code = $this->getFixture('MissingHandleMethodAction.php');

        $nodes = $this->parser->parse($code);
        $classNode = $this->getClassNode($nodes);

        $this->assertNotNull($classNode, 'Should find a class node');

        $result = $this->rule->refactor($classNode);

        $this->assertInstanceOf(Class_::class, $result);

        // Verify handle() method was added
        $hasHandle = false;
        foreach ($result->stmts as $stmt) {
            if ($stmt instanceof ClassMethod && $stmt->name->toString() === 'handle') {
                $hasHandle = true;
                $this->assertTrue($stmt->isPublic(), 'handle() method should be public');
                break;
            }
        }

        $this->assertTrue($hasHandle, 'handle() method should be added');
    }

    #[Test]
    public function does_not_modify_action_with_handle_method(): void
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
