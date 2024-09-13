<?php

declare(strict_types=1);

namespace Yiisoft\Di\Command;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Config\ConfigInterface;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\CallableDefinition;
use Yiisoft\Definitions\ValueDefinition;
use Yiisoft\Di\Helpers\DefinitionNormalizer;
use Yiisoft\VarDumper\VarDumper;

#[AsCommand(
    name: 'debug:container',
    description: 'Show information about container',
)]
final class DebugContainerCommand extends Command
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::IS_ARRAY, 'Service ID')
            ->addOption('groups', null, InputOption::VALUE_NONE, 'Show groups')
            ->addOption('group', 'g', InputOption::VALUE_REQUIRED, 'Show group');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $this->container->get(ConfigInterface::class);

        $io = new SymfonyStyle($input, $output);

        if ($input->hasArgument('id') && !empty($ids = $input->getArgument('id'))) {
            $build = $this->getConfigBuild($config);
            foreach ($ids as $id) {
                $definition = null;
                foreach ($build as $definitions) {
                    if (array_key_exists($id, $definitions)) {
                        $definition = $definitions[$id];
                    }
                }
                if ($definition === null) {
                    $io->error(
                        sprintf(
                            'Service "%s" not found.',
                            $id,
                        )
                    );
                    continue;
                }
                $io->title($id);

                $normalizedDefinition = DefinitionNormalizer::normalize($definition, $id);
                if ($normalizedDefinition instanceof ArrayDefinition) {
                    $definitionList = ['ID' => $id];
                    if (class_exists($normalizedDefinition->getClass())) {
                        $definitionList[] = ['Class' => $normalizedDefinition->getClass()];
                    }
                    if (!empty($normalizedDefinition->getConstructorArguments())) {
                        $definitionList[] = [
                            'Constructor' => $this->export(
                                $normalizedDefinition->getConstructorArguments()
                            ),
                        ];
                    }
                    if (!empty($normalizedDefinition->getMethodsAndProperties())) {
                        $definitionList[] = [
                            'Methods' => $this->export(
                                $normalizedDefinition->getMethodsAndProperties()
                            ),
                        ];
                    }
                    if (isset($definition['tags'])) {
                        $definitionList[] = ['Tags' => $this->export($definition['tags'])];
                    }

                    $io->definitionList(...$definitionList);

                    continue;
                }
                if ($normalizedDefinition instanceof CallableDefinition || $normalizedDefinition instanceof ValueDefinition) {
                    $io->text(
                        $this->export($definition)
                    );
                    continue;
                }

                $output->writeln([
                    $id,
                    VarDumper::create($normalizedDefinition)->asString(),
                ]);
            }

            return self::SUCCESS;
        }

        if ($input->hasOption('groups') && $input->getOption('groups')) {
            $build = $this->getConfigBuild($config);
            $groups = array_keys($build);
            sort($groups);

            $io->table(['Groups'], array_map(static fn ($group) => [$group], $groups));

            return self::SUCCESS;
        }
        if ($input->hasOption('group') && !empty($group = $input->getOption('group'))) {
            $data = $config->get($group);
            ksort($data);

            $rows = $this->getGroupServices($data);

            $table = new Table($output);
            $table
                ->setHeaderTitle($group)
                ->setHeaders(['Service', 'Definition'])
                ->setRows($rows);
            $table->render();

            return self::SUCCESS;
        }

        $build = $this->getConfigBuild($config);

        foreach ($build as $group => $data) {
            $rows = $this->getGroupServices($data);

            $table = new Table($output);
            $table
                ->setHeaderTitle($group)
                ->setHeaders(['Group', 'Services'])
                ->setRows($rows);
            $table->render();
        }

        return self::SUCCESS;
    }

    private function getConfigBuild(mixed $config): array
    {
        $reflection = new ReflectionClass($config);
        $buildReflection = $reflection->getProperty('build');
        $buildReflection->setAccessible(true);
        return $buildReflection->getValue($config);
    }

    protected function getGroupServices(array $data): array
    {
        $rows = [];
        foreach ($data as $id => $definition) {
            $class = '';
            if (is_string($definition)) {
                $class = $definition;
            }
            if (is_array($definition)) {
                $class = $definition['class'] ?? $id;
            }
            if (is_object($definition)) {
                $class = $definition::class;
            }

            $rows[] = [
                $id,
                $class,
            ];
        }
        return $rows;
    }

    protected function export(mixed $value): string
    {
        return VarDumper::create($value)->asString();
    }
}

