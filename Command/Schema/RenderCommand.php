<?php

declare(strict_types=1);

/*
 * This file is part of the slince/cycle-bundle package.
 *
 * (c) Slince <taosikai@yeah.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Slince\CycleBundle\Command\Schema;

use Cycle\ORM\Schema;
use Cycle\Schema\Renderer\MermaidRenderer\MermaidRenderer;
use Cycle\Schema\Renderer\OutputSchemaRenderer;
use Cycle\Schema\Renderer\PhpSchemaRenderer;
use Cycle\Schema\Renderer\SchemaToArrayConverter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'cycle:schema:render', description: 'Render available CycleORM schemas')]
final class RenderCommand extends Command
{
    protected SchemaToArrayConverter $converter;
    protected Schema $schema;

    public function __construct(SchemaToArrayConverter $converter, Schema $schema)
    {
        parent::__construct();
        $this->converter = $converter;
        $this->schema = $schema;
    }

    protected function configure()
    {
        $this->addOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (mermaid, php, color, or plain text)', 'plain');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $renderer = match ($input->getOption('format')) {
            'mermaid' => new MermaidRenderer(),
            'php' => new PhpSchemaRenderer(),
            'color' => new OutputSchemaRenderer(OutputSchemaRenderer::FORMAT_CONSOLE_COLOR),
            'plain', 'text' => new OutputSchemaRenderer(OutputSchemaRenderer::FORMAT_PLAIN_TEXT),
            default => throw new \InvalidArgumentException(
                sprintf("Format `%s` isn't supported.", $input->getOption('format'))
            )
        };

        $io = new SymfonyStyle($input, $output);

        $io->info($renderer->render($this->converter->convert($this->schema)));

        return self::SUCCESS;
    }
}
