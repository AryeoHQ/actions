<?php

declare(strict_types=1);

namespace Tests\Tooling\Actions\Rector\Rules;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tooling\Actions\Rector\Rules\ActionCannotUseQueueable;

#[CoversClass(ActionCannotUseQueueable::class)]
class ActionCannotUseQueueableTest extends TestCase
{
    private ActionCannotUseQueueable $rule;

    private ParserFactory|Parser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = app(ActionCannotUseQueueable::class);
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
    }

    private function getFixture(string $filename): string
    {
        return file_get_contents(__DIR__.'/../../Fixtures/Variations/'.$filename);
    }

    #[Test]
    public function removes_queueable_trait_from_action(): void
    {
        $code = $this->getFixture('ActionWithQueueable.php');

        $nodes = $this->parser->parse($code);
        $classNode = $this->getClassNode($nodes);

        $this->assertNotNull($classNode, 'Should find a class node');

        $result = $this->rule->refactor($classNode);

        $this->assertInstanceOf(Class_::class, $result);

        // Verify Queueable trait was removed
        $hasQueueable = false;
        foreach ($result->stmts as $stmt) {
            if ($stmt instanceof TraitUse) {
                foreach ($stmt->traits as $trait) {
                    if ($trait->toString() === 'Queueable') {
                        $hasQueueable = true;
                    }
                }
            }
        }

        $this->assertFalse($hasQueueable, 'Queueable trait should be removed');
    }

    #[Test]
    public function does_not_modify_action_without_queueable(): void
    {
        $code = $this->getFixture('ValidAction.php');

        $nodes = $this->parser->parse($code);
        $classNode = $this->getClassNode($nodes);

        $this->assertNotNull($classNode, 'Should find a class node');

        $result = $this->rule->refactor($classNode);

        $this->assertNull($result);
    }

    #[Test]
    public function removes_legacy_queueable_trait_from_action(): void
    {
        $code = $this->getFixture('ActionWithLegacyQueueable.php');

        $nodes = $this->parser->parse($code);
        $classNode = $this->getClassNode($nodes);

        $this->assertNotNull($classNode, 'Should find a class node');

        $result = $this->rule->refactor($classNode);

        $this->assertInstanceOf(Class_::class, $result);

        // Verify Queueable trait was removed
        $hasQueueable = false;
        foreach ($result->stmts as $stmt) {
            if ($stmt instanceof TraitUse) {
                foreach ($stmt->traits as $trait) {
                    if ($trait->toString() === 'Queueable') {
                        $hasQueueable = true;
                    }
                }
            }
        }

        $this->assertFalse($hasQueueable, 'Legacy Queueable trait should be removed');
    }

    /**
     * @param  array<\PhpParser\Node\Stmt>  $nodes
     */
    private function getClassNode(array $nodes): null|Class_
    {
        foreach ($nodes as $node) {
            if ($node instanceof Namespace_) {
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
