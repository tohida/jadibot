<?php
namespace Longman\TelegramBot\Commands\UserCommands;
use Longman\TelegramBot\Commands\Command;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
/**
 * User "/help" command
 */
class HelpCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'help';

    /**
     * @var string
     */
    protected $description = 'نمایش راهنما';

    /**
     * @var string
     */
    protected $usage = '/help یا /help <command>';

    /**
     * @var string
     */
    protected $version = '1.2.0';

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $message     = $this->getMessage();
        $chat_id     = $message->getFrom()->getId();
        $command_str = trim($message->getText(true));

        $data = [
            'chat_id'    => $chat_id,
            'parse_mode' => 'markdown',
        ];

        list($all_commands, $user_commands, $admin_commands) = $this->getUserAdminCommands();

        // If no command parameter is passed, show the list.
        if ($command_str === '') {
            $data['text'] = '*فرمان ها*:' . PHP_EOL;
            foreach ($user_commands as $user_command) {
                $data['text'] .= '/' . $user_command->getName() . ' - ' . $user_command->getDescription() . PHP_EOL;
            }

            if (count($admin_commands) > 0) {
                $data['text'] .= PHP_EOL . '*فرمان های مدیریت*:' . PHP_EOL;
                foreach ($admin_commands as $admin_command) {
                    $data['text'] .= '/' . $admin_command->getName() . ' - ' . $admin_command->getDescription() . PHP_EOL;
                }
            }

            $data['text'] .= PHP_EOL . 'برای راهنمای یک فرمان از این دستور استفاده کنید:'. PHP_EOL .' /help <command>';

            return Request::sendMessage($data);
        }

        $command_str = str_replace('/', '', $command_str);
        if (isset($all_commands[$command_str])) {
            $command      = $all_commands[$command_str];
            $data['text'] = sprintf(
                'فرمان: %s (v%s)' . PHP_EOL .
                'توضیحات: %s' . PHP_EOL .
                'استفاده: %s',
                $command->getName(),
                $command->getVersion(),
                $command->getDescription(),
                $command->getUsage()
            );

            return Request::sendMessage($data);
        }

        $data['text'] = 'راهنمایی برای فرمان  /' . $command_str . '  پیدا نشد';

        return Request::sendMessage($data);
    }

    /**
     * Get all available User and Admin commands to display in the help list.
     *
     * @return Command[][]
     */
    protected function getUserAdminCommands()
    {
        // Only get enabled Admin and User commands that are allowed to be shown.
        /** @var Command[] $commands */
        $commands = array_filter($this->telegram->getCommandsList(), function ($command) {
            /** @var Command $command */
            return !$command->isSystemCommand() && $command->showInHelp() && $command->isEnabled();
        });

        $user_commands = array_filter($commands, function ($command) {
            /** @var Command $command */
            return $command->isUserCommand();
        });

        $admin_commands = array_filter($commands, function ($command) {
            /** @var Command $command */
            return $command->isAdminCommand();
        });

        ksort($commands);
        ksort($user_commands);
        ksort($admin_commands);

        return [$commands, $user_commands, $admin_commands];
    }
}
