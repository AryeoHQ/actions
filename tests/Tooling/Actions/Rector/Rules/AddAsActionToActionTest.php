<?php

declare(strict_types=1);

namespace Tests\Tooling\Actions\Rector\Rules;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tooling\Actions\Rector\Rules\AddAsActionToAction;

#[CoversClass(AddAsActionToAction::class)]
class AddAsActionToActionTest extends TestCase
{
    private AddAsActionToAction $rule;

    private ParserFactory|Parser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = app(AddAsActionToAction::class);
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
    }

    private function getFixture(string $filename): string
    {
        return file_get_contents(__DIR__.'/../../Fixtures/Variations/'.$filename);
    }

    #[Test]
    public function adds_trait_when_contract_is_implemented(): void
    {
        $code = $this->getFixture('MissingAsActionTraitAction.php');

        $nodes = $this->parser->parse($code);
        $classNode = $this->getClassNode($nodes);

        $this->assertNotNull($classNode, 'Should find a class node');

        $result = $this->rule->refactor($classNode);

        $this->assertInstanceOf(Class_::class, $result);
        $this->assertNotEmpty($result->stmts);
    }

    #[Test]
    public function does_not_modify_complete_class(): void
    {
        $code = $this->getFixture('CompleteAction.php');

        $nodes = $this->parser->parse($code);
        $classNode = $this->getClassNode($nodes);

        $this->assertNotNull($classNode, 'Should find a class node');

        $result = $this->rule->refactor($classNode);

        $this->assertNull($result);
    }

    #[Test]
    public function does_not_modify_non_action_class(): void
    {
        $code = $this->getFixture('NonActionClass.php');

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
