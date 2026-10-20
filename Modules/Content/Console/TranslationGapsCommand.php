<?php

namespace Modules\Content\Console;

use Illuminate\Console\Command;
use Modules\Content\Services\TranslationSources;

class TranslationGapsCommand extends Command
{
    protected $signature = 'translations:gaps {--path=TRANSLATION_GAPS.md : Where to write the report}';

    protected $description = 'List admin-entered content that has no Bangla version yet';

    public function handle(TranslationSources $sources): int
    {
        file_put_contents(base_path((string) $this->option('path')), $sources->gapsMarkdown());
        $this->info('Wrote '.$this->option('path'));

        return self::SUCCESS;
    }
}
