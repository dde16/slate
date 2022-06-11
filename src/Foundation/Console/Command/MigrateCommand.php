<?php declare(strict_types = 1);

namespace Slate\Foundation\Console\Command {

    use DateTime;
    use RuntimeException;
    use Slate\Data\Table;
    use Slate\Foundation\Console\Command;
    use Slate\IO\Directory;
    use Slate\Mvc\Env;

    class MigrateCommand extends Command {
        public final const NAME = "migrate";
        public const ARGUMENTS = [
            "migrationName" => [
                "aliases" => ["-n", "--name"]
            ]
        ];

        public function handle(?string $migrationName = null): void {
            $migrationsNamespace      = Env::get("orm.namespace.migrations");
            $migrationsPath      = Env::get("orm.path.migrations");

            $migrationsDirectory = new Directory($migrationsPath);
            $migrationsDirectory->open(create: false);
            
            foreach($migrationsDirectory->order("name", "desc") as $migrationFile) {
                $migrationClass = \Str::removeSuffix($migrationsNamespace, "\\")."\\".$migrationFile["filename"];

                $migrationInstance = new $migrationClass;

                $migrationElapsed = elapsed(fn(): mixed => $migrationInstance->up());

                debug($migrationFile["filename"]." [{$migrationElapsed[1]}ms]");
            }

            $migrationsDirectory->close();
        }
    }
}

?>