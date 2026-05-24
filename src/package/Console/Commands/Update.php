<?php

namespace PragmaRX\CountriesLaravel\Package\Console\Commands;

use PragmaRX\CountriesLaravel\Package\Update\Updater;

/**
 * @codeCoverageIgnore
 */
class Update extends Base
{
    protected $name = 'countries:update';

    protected $description = 'Update all data';

    public function handle(): void
    {
        app(Updater::class)->update($this);

        $this->info('Updated.');
    }
}
