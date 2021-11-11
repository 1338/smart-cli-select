<?php


namespace SmartCliSelect;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class SmartCliSelect
 * @package Services
 * @author Max van der Sluis <mvandersluis@cornerstonesmedia.nl>
 * @since Version 0.0.1
 */
class SmartCliSelect
{
    private SymfonyStyle $io;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param QuestionHelper $questionHelper
     */
    public function __construct(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    public function smartSelect(
        string $questionString,
               $allChoices = [],
               $preSelected = [],
               $force = false,
               $createStructure = []
    ): array
    {
        $specialCases = ['[ALL]', '[FINISHED]'];

        $chosen = [];
        foreach ($preSelected as $preSelectedOption) {
            if(in_array($preSelectedOption, $allChoices)) {
                $chosen[] = $preSelectedOption;
            }
        }

        return $this->selectOptions(
            $questionString,
            $chosen,
            $allChoices,
            $specialCases,
            $force,
            $createStructure
        );
    }

    private function selectOptions($questionString, $chosen, $allChoices, $specialCases, $force, $createStructure): array
    {
        if($force) {
            return ['selected' => $chosen];
        }

        $return = [
            'selected' => [],
            'new' => []
        ];

        if(empty($createStructure) === false) {
            $specialCases[] = '[NEW]';
        }

        $shouldAsk = true;
        while ($shouldAsk) {
            $selectAbles = array_merge($allChoices, $specialCases);
            if(!empty($chosen)) {
                $preSelectChoice = '[FINISHED]';
            } elseif (empty($allChoices) && in_array('[NEW]', $selectAbles)) {
                $preSelectChoice = '[NEW]';
            } else {
                $preSelectChoice = $selectAbles[array_key_first($selectAbles)];
            }
            $this->io->writeln('Current Selection:');
            if(empty($chosen) === false) {
                $this->io->text($chosen);
            }
            $currentChoice = $this->io->choice($questionString, $selectAbles, $preSelectChoice);
            if(in_array($currentChoice, $specialCases)) {
                switch ($currentChoice) {
                    case '[FINISHED]':
                        $shouldAsk = false;
                        break;
                    case '[NEW]':
                        $option = $this->createOption($createStructure);
                        if($option) {
                            $return['new'][$option['name']] = $option['data'];
                            $allChoices[] = $option['name'];
                            if($this->io->ask("Add new option to chosen? [" . $option['name'] . ']')){
                                $chosen[] = $option['name'];
                            }
                        } else {
                            $this->io->writeln("New option is invalid");
                        }
                        break;
                    case '[ALL]':
                        $chosen = $allChoices;
                        $shouldAsk = false;
                        break;
                }
            } else {
                $alreadySelected = array_search($currentChoice, $chosen);
                if($alreadySelected !== false) {
                    unset($chosen[$alreadySelected]);
                } else {
                    $chosen[] = $currentChoice;
                }
            }
        }
        $return['selected'] = $chosen;

        return $return;
    }

    private function createOption(array $createStructure) {
        if(isset($createStructure['type'])) {
            // object type
            $objectFullClassPath = $createStructure['type'];

            $newObject = new $objectFullClassPath();
            $objectMethods = get_class_methods($newObject);

            $methodsToAsk = [];

            if(isset($createStructure['options'])) {
                foreach ($createStructure['options'] as $preSelectOption) {
                    $methodName = array_search($preSelectOption, $objectMethods);
                    if($methodName === false) {
                        $methodName = array_search('set'.ucfirst($preSelectOption), $objectMethods);
                        var_dump($preSelectOption, 'set'.ucfirst($preSelectOption), $objectMethods);
                    };
                    if($methodName !== false) {
                        $methodsToAsk[] = $objectMethods[$methodName];
                    }

                }
            } else {
                $methodsToAsk = $objectMethods;
            }

            foreach ($methodsToAsk as $methodQuestion) {
                if($this->io->ask("Set method $methodQuestion for $objectFullClassPath", 'y') == 'y') {
                    $optionValue = $this->io->ask("Value to fill for $methodQuestion for $objectFullClassPath");
                    if($optionValue != '') {
                        if(in_array($optionValue, ['true', 'false'])) {
                            $optionValue = (bool)$optionValue;
                        }
                        if($this->io->ask("$optionValue correct for $methodQuestion", 'y')) {
                            $newObject->{$methodQuestion}($optionValue);
                        }
                    }
                }
            }
            $objectName = $this->io->ask("Name for $objectFullClassPath");
            return [
                'name' => $objectName,
                'data' => $newObject
            ];

        } else {
            if(isset($createStructure['options']) === false) {
                return false;
            }
            $selectedData = [];
            foreach ($createStructure['options'] as $currentOption) {
                $optionAnswer = $this->io->ask("Value for $currentOption");
                if($this->io->ask("$optionAnswer correct for $currentOption", 'y')) {
                    $selectedData[$currentOption] = $optionAnswer;
                }
            }
            $arrayName = $this->io->ask("Name for item");
            return [
                'name' => $arrayName,
                'data' => $selectedData
            ];
        }
    }
}