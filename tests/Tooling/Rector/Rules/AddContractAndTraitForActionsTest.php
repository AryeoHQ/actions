<?php

declare(strict_types=1);

namespace Tests\Tooling\Rector\Rules;

use PhpParser\Node\Stmt\Class_;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use Tooling\Rector\Rules\AddContractAndTraitForActions;

#[CoversClass(AddContractAndTraitForActions::class)]
class AddContractAndTraitForActionsTest extends TestCase
{
    private AddContractAndTraitForActions $rule;

    private $parser;

    protected function setUp(): void
    {
        $this->rule = app(AddContractAndTraitForActions::class);
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
    }

    private function getFixture(string $filename): string
    {
        return file_get_contents(__DIR__.'/../../../Fixtures/Variations/'.$filename);
    }

    public function test_adds_contract_when_trait_is_used(): void
    {
        $code = $this->getFixture('MissingActionContractAction.php');

        $nodes = $this->parser->parse($code);
        $classNode = $this->getClassNode($nodes);

        $this->assertNotNull($classNode, 'Should find a class node');

        $result = $this->rule->refactor($classNode);

        $this->assertInstanceOf(Class_::class, $result);
        $this->assertTrue($this->rule->implementsActionContract($result));
        $this->assertTrue($this->rule->usesAsActionTrait($result));
    }

    public function test_adds_trait_when_contract_is_implemented(): void
    {
        $code = $this->getFixture('MissingAsActionTraitAction.php');

        $nodes = $this->parser->parse($code);
        $classNode = $this->getClassNode($nodes);

        $this->assertNotNull($classNode, 'Should find a class node');

        $result = $this->rule->refactor($classNode);

        $this->assertInstanceOf(Class_::class, $result);
        $this->assertTrue($this->rule->implementsActionContract($result));
        $this->assertTrue($this->rule->usesAsActionTrait($result));
    }

    public function test_does_not_modify_complete_class(): void
    {
        $code = $this->getFixture('CompleteAction.php');

        $nodes = $this->parser->parse($code);
        $classNode = $this->getClassNode($nodes);

        $this->assertNotNull($classNode, 'Should find a class node');

        $result = $this->rule->refactor($classNode);

        $this->assertNull($result); // Should not modify already complete class
    }

    public function test_does_not_modify_non_action_class(): void
    {
        $code = $this->getFixture('NonActionClass.php');

        $nodes = $this->parser->parse($code);
        $classNode = $this->getClassNode($nodes);

        $this->assertNotNull($classNode, 'Should find a class node');

        $result = $this->rule->refactor($classNode);

        $this->assertNull($result); // Should not modify non-Builder classes
    }

    public function test_makes_action_class_final(): void
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

    private function getClassNode(array $nodes): ?Class_
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
