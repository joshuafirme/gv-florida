<?php

use Illuminate\Support\Facades\Route;


Route::
        namespace('Auth')->group(function () {
            Route::middleware('admin.guest')->group(function () {
                Route::controller('LoginController')->group(function () {
                    Route::get('/', 'showLoginForm')->name('login');
                    Route::post('/', 'login')->name('login');
                    Route::get('logout', 'logout')->middleware('admin')->withoutMiddleware('admin.guest')->name('logout');
                });

                // Admin Password Reset
                Route::controller('ForgotPasswordController')->prefix('password')->name('password.')->group(function () {
                    Route::get('reset', 'showLinkRequestForm')->name('reset');
                    Route::post('reset', 'sendResetCodeEmail');
                    Route::get('code-verify', 'codeVerify')->name('code.verify');
                    Route::post('verify-code', 'verifyCode')->name('verify.code');
                });

                Route::controller('ResetPasswordController')->group(function () {
                    Route::get('password/reset/{token}', 'showResetForm')->name('password.reset.form');
                    Route::post('password/reset/change', 'reset')->name('password.change');
                });
            });
        });

Route::middleware('admin')->group(function () {
    Route::controller('AdminController')->group(function () {
        Route::get('unauthorize', function () {
            $pageTitle = "Unauthorize";
            return view('admin.auth.unauthorize', compact('pageTitle'));
        })->name('unauthorize');

        Route::get('dashboard', 'dashboard')->name('dashboard')->middleware(['role:admin.dashboard']);
        Route::get('payment-chart', 'paymentReport')->name('payment.chart');
        Route::get('profile', 'profile')->name('profile');
        Route::post('profile', 'profileUpdate')->name('profile.update');
        Route::get('password', 'password')->name('password');
        Route::post('password', 'passwordUpdate')->name('password.update');
        //Notification
        Route::get('notifications', 'notifications')->name('notifications');
        Route::get('notification/read/{id}', 'notificationRead')->name('notification.read');
        Route::get('notifications/read-all', 'readAllNotification')->name('notifications.read.all');
        Route::post('notifications/delete-all', 'deleteAllNotification')->name('notifications.delete.all');
        Route::post('notifications/delete-single/{id}', 'deleteSingleNotification')->name('notifications.delete.single');

        //Report Bugs
        Route::get('request-report', 'requestReport')->name('request.report');
        Route::post('request-report', 'reportSubmit');

        Route::get('download-attachments/{file_hash}', 'downloadAttachment')->name('download.attachment');
    });

    Route::controller('AdminUserController')->prefix('admin')->group(function () {
        Route::get('users', 'index')->name('users');
        Route::post('users', 'store')->name('users.store');
        Route::post('users/{id}', 'update')->name('users.update');
        Route::post('users/remove/{id}', 'remove')->name('users.remove');
    });

    Route::controller('RoleController')->group(function () {
        Route::get('roles', 'index')->name('roles');
        Route::post('roles', 'store')->name('roles.store');
        Route::post('roles/{id}', 'update')->name('roles.update');
        Route::post('roles/remove/{id}', 'remove')->name('roles.remove');
    });

    Route::controller('CounterController')->name('counter.')->prefix('counter')->group(function () {
        Route::get('', 'counters')->name('index');
        Route::get('schedule-board/{id}', 'scheduleBoard')->name('scheduleBoard');
        Route::get('schedule-board/json/{id}', 'scheduleBoardJSON')->name('scheduleBoardJSON');
        Route::post('/{id?}', 'counterStore')->name('store');
        Route::post('status/{id}', 'status')->name('status');
    });

    Route::controller('ManageFleetController')->name('fleet.')->prefix('fleet')->group(function () {

        Route::prefix('layouts')->name('layouts.')->group(function () {
            Route::get('', 'layout')->name('index');
            Route::post('store/{id?}', 'layoutStore')->name('store');
            Route::post('remove/{id}', 'removeLayout')->name('remove');
        });

        Route::prefix('type')->name('type.')->group(function () {
            Route::get('', 'type')->name('index');
            Route::post('store/{id?}', 'typeStore')->name('store');
            Route::post('status/{id}', 'typeStatus')->name('status');
        });

        Route::prefix('vehicles')->name('vehicles.')->group(function () {
            Route::get('', 'vehicles')->name('index');
            Route::post('store/{id?}', 'vehiclesStore')->name('store');
            Route::post('status/{id}', 'vehicleStatus')->name('status');
        });
    });

    Route::controller('ManageTripController')->prefix('manage')->name('trip.')->group(function () {
        Route::prefix('trip')->group(function () {
            Route::get('/', 'trips')->name('list');
            Route::post('/store/{id?}', 'tripStore')->name('store');
            Route::post('status/{id}', 'tripStatus')->name('status');
        });

        Route::prefix('route')->name('route.')->group(function () {
            Route::get('/', 'routeList')->name('index');
            Route::get('create', 'routeCreate')->name('create');
            Route::post('store', 'routeStore')->name('store');
            Route::get('edit/{id}', 'routeEdit')->name('edit');
            Route::post('update/{id}', 'routeUpdate')->name('update');
            Route::post('status/{id}', 'routeStatus')->name('status');
        });

        Route::prefix('schedule')->name('schedule.')->group(function () {
            Route::get('/', 'schedules')->name('index');
            Route::post('store/{id?}', 'scheduleStore')->name('store');
            Route::post('status/{id}', 'scheduleStatus')->name('status');
        });

        Route::prefix('assigned-vehicle')->name('vehicle.assign.')->group(function () {
            Route::get('/', 'assignedVehicleLists')->name('index');
            Route::post('store/{id?}', 'assignVehicle')->name('store');
            Route::post('status/{id}', 'assignedVehicleStatus')->name('status');

        });
    });



    Route::controller('VehicleTicketController')->prefix('manage')->name('trip.ticket.')->group(function () {

        Route::prefix('price')->name('price.')->group(function () {
            Route::get('/', 'ticketPriceList')->name('index');
            Route::get('create', 'ticketPriceCreate')->name('create');
            Route::post('store', 'ticketPriceStore')->name('store');
            Route::get('edit/{id}', 'ticketPriceEdit')->name('edit');
            Route::post('update/{id}', 'ticketPriceUpdate')->name('update');
            Route::post('delete/{id}', 'ticketPriceDelete')->name('delete');
        });

        Route::get('price/check_price', 'checkTicketPrice')->name('check_price');
        Route::get('route-data', 'getRouteData')->name('get_route_data');
    });

    Route::controller('VehicleTicketController')->prefix('tickets')->name('vehicle.ticket.')->group(function () {
        Route::get('booked', 'booked')->name('booked');
        Route::get('pending', 'pending')->name('pending');
        Route::get('rejected', 'rejected')->name('rejected');
        Route::get('all', 'list')->name('list');
        Route::get('pending/details/{id}', 'pendingDetails')->name('pending.details');
        Route::get('{scope}/search', 'search')->name('search');
    });





    // Users Manage user
    Route::controller('ManageUsersController')->name('users.')->prefix('users')->group(function () {
        Route::get('/', 'allUsers')->name('all');
        Route::get('active', 'activeUsers')->name('active');
        Route::get('banned', 'bannedUsers')->name('banned');
        Route::get('email-verified', 'emailVerifiedUsers')->name('email.verified');
        Route::get('email-unverified', 'emailUnverifiedUsers')->name('email.unverified');
        Route::get('mobile-unverified', 'mobileUnverifiedUsers')->name('mobile.unverified');
        Route::get('mobile-verified', 'mobileVerifiedUsers')->name('mobile.verified');

        Route::get('detail/{id}', 'detail')->name('detail');
        Route::post('update/{id}', 'update')->name('update');
        Route::post('add-sub-balance/{id}', 'addSubBalance')->name('add.sub.balance');
        Route::get('send-notification/{id}', 'showNotificationSingleForm')->name('notification.single');
        Route::post('send-notification/{id}', 'sendNotificationSingle')->name('notification.single');
        Route::get('login/{id}', 'login')->name('login');
        Route::post('status/{id}', 'status')->name('status');

        Route::get('send-notification', 'showNotificationAllForm')->name('notification.all');
        Route::post('send-notification', 'sendNotificationAll')->name('notification.all.send');
        Route::get('list', 'list')->name('list');
        Route::get('count-by-segment/{methodName}', 'countBySegment')->name('segment.count');
        Route::get('notification-log/{id}', 'notificationLog')->name('notification.log');
    });

    // Deposit Gateway
    Route::name('gateway.')->prefix('gateway')->group(function () {
        // Automatic Gateway
        Route::controller('AutomaticGatewayController')->prefix('automatic')->name('automatic.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('edit/{alias}', 'edit')->name('edit');
            Route::post('update/{code}', 'update')->name('update');
            Route::post('remove/{id}', 'remove')->name('remove');
            Route::post('status/{id}', 'status')->name('status');
        });


        // Manual Methods
        Route::controller('ManualGatewayController')->prefix('manual')->name('manual.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('new', 'create')->name('create');
            Route::post('new', 'store')->name('store');
            Route::get('edit/{alias}', 'edit')->name('edit');
            Route::post('update/{id}', 'update')->name('update');
            Route::post('status/{id}', 'status')->name('status');
        });
    });


    // DEPOSIT SYSTEM
    Route::controller('DepositController')->prefix('deposit')->name('deposit.')->group(function () {
        Route::get('all/{user_id?}', 'deposit')->name('list');
        Route::get('pending/{user_id?}', 'pending')->name('pending');
        Route::get('rejected/{user_id?}', 'rejected')->name('rejected');
        Route::get('approved/{user_id?}', 'approved')->name('approved');
        Route::get('successful/{user_id?}', 'successful')->name('successful');
        Route::get('initiated/{user_id?}', 'initiated')->name('initiated');
        Route::get('details/{id}', 'details')->name('details');
        Route::post('reject', 'reject')->name('reject');
        Route::post('approve/{id}', 'approve')->name('approve');
    });



    // Report
    Route::controller('ReportController')->prefix('report')->name('report.')->group(function () {
        Route::get('transaction/{user_id?}', 'transaction')->name('transaction');
        Route::get('login/history', 'loginHistory')->name('login.history');
        Route::get('login/ipHistory/{ip}', 'loginIpHistory')->name('login.ipHistory');
        Route::get('notification/history', 'notificationHistory')->name('notification.history');
        Route::get('email/detail/{id}', 'emailDetails')->name('email.details');
    });


    // Admin Support
    Route::controller('SupportTicketController')->prefix('ticket')->name('ticket.')->group(function () {
        Route::get('/', 'tickets')->name('index');
        Route::get('pending', 'pendingTicket')->name('pending');
        Route::get('closed', 'closedTicket')->name('closed');
        Route::get('answered', 'answeredTicket')->name('answered');
        Route::get('view/{id}', 'ticketReply')->name('view');
        Route::post('reply/{id}', 'replyTicket')->name('reply');
        Route::post('close/{id}', 'closeTicket')->name('close');
        Route::get('download/{attachment_id}', 'ticketDownload')->name('download');
        Route::post('delete/{id}', 'ticketDelete')->name('delete');
    });


    // Language Manager
    Route::controller('LanguageController')->prefix('language')->name('language.')->group(function () {
        Route::get('/', 'langManage')->name('manage');
        Route::post('/', 'langStore')->name('manage.store');
        Route::post('delete/{id}', 'langDelete')->name('manage.delete');
        Route::post('update/{id}', 'langUpdate')->name('manage.update');
        Route::get('edit/{id}', 'langEdit')->name('key');
        Route::post('import', 'langImport')->name('import.lang');
        Route::post('store/key/{id}', 'storeLanguageJson')->name('store.key');
        Route::post('delete/key/{id}', 'deleteLanguageJson')->name('delete.key');
        Route::post('update/key/{id}', 'updateLanguageJson')->name('update.key');
        Route::get('get-keys', 'getKeys')->name('get.key');
    });

    Route::controller('GeneralSettingController')->group(function () {

        Route::get('system-setting', 'systemSetting')->name('setting.system');

        // General Setting
        Route::get('general-setting', 'general')->name('setting.general');
        Route::post('general-setting', 'generalUpdate');

        Route::get('setting/social/credentials', 'socialiteCredentials')->name('setting.socialite.credentials');
        Route::post('setting/social/credentials/update/{key}', 'updateSocialiteCredential')->name('setting.socialite.credentials.update');
        Route::post('setting/social/credentials/status/{key}', 'updateSocialiteCredentialStatus')->name('setting.socialite.credentials.status.update');

        //configuration
        Route::get('setting/system-configuration', 'systemConfiguration')->name('setting.system.configuration');
        Route::post('setting/system-configuration', 'systemConfigurationSubmit');

        // Logo-Icon
        Route::get('setting/logo-icon', 'logoIcon')->name('setting.logo.icon');
        Route::post('setting/logo-icon', 'logoIconUpdate')->name('setting.logo.icon');

        //Custom CSS
        Route::get('custom-css', 'customCss')->name('setting.custom.css');
        Route::post('custom-css', 'customCssSubmit');

        Route::get('sitemap', 'sitemap')->name('setting.sitemap');
        Route::post('sitemap', 'sitemapSubmit');

        Route::get('robot', 'robot')->name('setting.robot');
        Route::post('robot', 'robotSubmit');

        //Cookie
        Route::get('cookie', 'cookie')->name('setting.cookie');
        Route::post('cookie', 'cookieSubmit');

        //maintenance_mode
        Route::get('maintenance-mode', 'maintenanceMode')->name('maintenance.mode');
        Route::post('maintenance-mode', 'maintenanceModeSubmit');

    });

    //Notification Setting
    Route::name('setting.notification.')->controller('NotificationController')->prefix('notification')->group(function () {
        //Template Setting
        Route::get('global/email', 'globalEmail')->name('global.email');
        Route::post('global/email/update', 'globalEmailUpdate')->name('global.email.update');

        Route::get('global/sms', 'globalSms')->name('global.sms');
        Route::post('global/sms/update', 'globalSmsUpdate')->name('global.sms.update');

        Route::get('global/push', 'globalPush')->name('global.push');
        Route::post('global/push/update', 'globalPushUpdate')->name('global.push.update');

        Route::get('templates', 'templates')->name('templates');
        Route::get('template/edit/{type}/{id}', 'templateEdit')->name('template.edit');
        Route::post('template/update/{type}/{id}', 'templateUpdate')->name('template.update');

        //Email Setting
        Route::get('email/setting', 'emailSetting')->name('email');
        Route::post('email/setting', 'emailSettingUpdate');
        Route::post('email/test', 'emailTest')->name('email.test');

        //SMS Setting
        Route::get('sms/setting', 'smsSetting')->name('sms');
        Route::post('sms/setting', 'smsSettingUpdate');
        Route::post('sms/test', 'smsTest')->name('sms.test');

        Route::get('notification/push/setting', 'pushSetting')->name('push');
        Route::post('notification/push/setting', 'pushSettingUpdate');
        Route::post('notification/push/setting/upload', 'pushSettingUpload')->name('push.upload');
        Route::get('notification/push/setting/download', 'pushSettingDownload')->name('push.download');
    });

    // Plugin
    Route::controller('ExtensionController')->prefix('extensions')->name('extensions.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('update/{id}', 'update')->name('update');
        Route::post('status/{id}', 'status')->name('status');
    });


    //System Information
    Route::controller('SystemController')->name('system.')->prefix('system')->group(function () {
        Route::get('info', 'systemInfo')->name('info');
        Route::get('server-info', 'systemServerInfo')->name('server.info');
        Route::get('optimize', 'optimize')->name('optimize');
        Route::get('optimize-clear', 'optimizeClear')->name('optimize.clear');
        Route::get('system-update', 'systemUpdate')->name('update');
        Route::post('system-update', 'systemUpdateProcess')->name('update.process');
        Route::get('system-update/log', 'systemUpdateLog')->name('update.log');
    });


    // SEO
    Route::get('seo', 'FrontendController@seoEdit')->name('seo');


    // Frontend
    Route::name('frontend.')->prefix('frontend')->group(function () {

        Route::controller('FrontendController')->group(function () {
            Route::get('index', 'index')->name('index');
            Route::get('templates', 'templates')->name('templates');
            Route::post('templates', 'templatesActive')->name('templates.active');
            Route::get('frontend-sections/{key?}', 'frontendSections')->name('sections');
            Route::post('frontend-content/{key}', 'frontendContent')->name('sections.content');
            Route::get('frontend-element/{key}/{id?}', 'frontendElement')->name('sections.element');
            Route::get('frontend-slug-check/{key}/{id?}', 'frontendElementSlugCheck')->name('sections.element.slug.check');
            Route::get('frontend-element-seo/{key}/{id}', 'frontendSeo')->name('sections.element.seo');
            Route::post('frontend-element-seo/{key}/{id}', 'frontendSeoUpdate');
            Route::post('remove/{id}', 'remove')->name('remove');
        });

        // Page Builder
        Route::controller('PageBuilderController')->group(function () {
            Route::get('manage-pages', 'managePages')->name('manage.pages');
            Route::get('manage-pages/check-slug/{id?}', 'checkSlug')->name('manage.pages.check.slug');
            Route::post('manage-pages', 'managePagesSave')->name('manage.pages.save');
            Route::post('manage-pages/update', 'managePagesUpdate')->name('manage.pages.update');
            Route::post('manage-pages/delete/{id}', 'managePagesDelete')->name('manage.pages.delete');
            Route::get('manage-section/{id}', 'manageSection')->name('manage.section');
            Route::post('manage-section/{id}', 'manageSectionUpdate')->name('manage.section.update');

            Route::get('manage-seo/{id}', 'manageSeo')->name('manage.pages.seo');
            Route::post('manage-seo/{id}', 'manageSeoStore');
        });
    });
});
