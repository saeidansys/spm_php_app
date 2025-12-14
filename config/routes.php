<?php

use App\Action\Ansys\AnsysJsonSpItemHistoryAction;
use App\Action\Ansys\AnsysJsonSpItemsAction;
use App\Action\Ansys\NewServicePartnerCreatedHookAction;
use App\Action\Ansys\ServicePartnerBulkProjectConsumedAction;
use App\Action\Ansys\ServicePartnerBulkProjectPlanAction;
use App\Action\Ansys\ServicePartnerBulkProjectUpdateAction;
use App\Action\Ansys\ServicePartnerDashboardAction;
use App\Action\Ansys\ServicePartnerLoginSubmitAction;
use App\Action\Ansys\ServicePartnerPlanUploadPostAction;
use App\Action\Ansys\ServicePartnerPortalAction;
use App\Action\Ansys\ServicePartnerProjectPlanAction;
use App\Action\Ansys\ServicePartnerProjectPlanConsumedAction;
use App\Action\Ansys\ServicePartnerProjectPlanUpdateAction;
use App\Action\Ansys\ServicePartnerProjectPlanUploadAction;
use App\Action\Ansys\ServicePartnerSubmitPpAction;
use App\Action\Ansys\ServicePartnerSubmitUploadEditsAction;
use App\Action\HomeAction;
use App\Middleware\ChallengeResolver;
use Slim\App;

return function (App $app) {

    $app->get('/', HomeAction::class)->setName('home');

    // html sp
    $app->get('/sploginform/{id}', ServicePartnerPortalAction::class)->setName('sploginform');
    $app->post('/sploginsubmit', ServicePartnerLoginSubmitAction::class)->setName('sploginsubmit');
    $app->get('/spdashboard/{id}', ServicePartnerDashboardAction::class)->setName('spdashboard');
    $app->get('/spbppuploadform', ServicePartnerBulkProjectPlanAction::class)->setName('spbppuploadform');
    $app->get('/spbppuuploadform', ServicePartnerBulkProjectUpdateAction::class)->setName('spbppuuploadform');
    $app->get('/spbpcuploadform', ServicePartnerBulkProjectConsumedAction::class)->setName('spbpcuploadform');
    $app->get('/spppview/{pid}', ServicePartnerProjectPlanAction::class)->setName('spppview');
    $app->get('/spppupload/{pid}', ServicePartnerProjectPlanUploadAction::class)->setName('spppupload');
    $app->get('/spppuupload/{pid}', ServicePartnerProjectPlanUpdateAction::class)->setName('spppuupload');
    $app->get('/sppcupload/{pid}', ServicePartnerProjectPlanConsumedAction::class)->setName('sppcupload');
    $app->post('/spppuploadsubmit', ServicePartnerPlanUploadPostAction::class)->setName('spppuploadsubmit');
    $app->get('/submitppffile', ServicePartnerSubmitPpAction::class)->setName('submitppffile');
    $app->post('/spsubmitedits', ServicePartnerSubmitUploadEditsAction::class)->setName('spsubmitedits');

    // api
    $app->get('/jsonspppitems', AnsysJsonSpItemsAction::class)->setName('jsonspppitems');
    $app->get('/jsonspppitemhistory', AnsysJsonSpItemHistoryAction::class)->setName('jsonspppitemhistory');

    // hooks sp
    $app->post('/newspcreated', NewServicePartnerCreatedHookAction::class)->setName('newspcreated')
        ->add(ChallengeResolver::class);

};
