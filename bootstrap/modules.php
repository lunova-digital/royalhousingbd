<?php

use Laramod\Admin\AdminModule;
use Modules\Account\AccountModule;
use Modules\Auth\AuthModule;
use Modules\Consultation\ConsultationModule;
use Modules\Content\ContentModule;
use Modules\Dashboard\DashboardModule;
use Modules\Home\HomeModule;
use Modules\Listing\ListingModule;
use Modules\Masterplan\MasterplanModule;
use Modules\Media\MediaModule;
use Modules\Service\ServiceModule;
use Modules\Settings\SettingsModule;
use Modules\Site\SiteModule;

return [
    SiteModule::class,
    SettingsModule::class,
    MediaModule::class,
    AuthModule::class,
    AccountModule::class,
    DashboardModule::class,
    ListingModule::class,
    MasterplanModule::class,
    ContentModule::class,
    ConsultationModule::class,
    ServiceModule::class,
    HomeModule::class,
    AdminModule::class,
];
