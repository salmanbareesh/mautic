<?php

declare(strict_types=1);

use Mautic\MarketplaceBundle\DTO\PackageDetail;
use Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions;
use Mautic\MarketplaceBundle\Service\RouteProvider;

/** @var PackageDetail $packageDetail */
$packageDetail = $packageDetail;

$view['slots']->set('headerTitle', $view->escape($packageDetail->getPackageBase()->getHumanPackageName()));
$view->extend('MauticCoreBundle:Default:content.html.php');

$buttons = [
    [
        'attr' => [
            'href' => $view['router']->path(RouteProvider::ROUTE_LIST),
        ],
        'btnText'   => $view['translator']->trans('mautic.core.form.close'),
        'iconClass' => 'fa fa-remove',
    ],
];

// @todo make the stability configurable
// @todo make the version configurable
try {
    $latestVersion = $packageDetail->getVersions()->findLatestVersionPackage();
} catch (\Throwable $e) {
    $latestVersionException = $e;
}

if (isset($latestVersion)) {
    $buttons[] = [
        'attr' => [
            'href'   => $latestVersion->getIssues(),
            'target' => '_blank',
            'rel'    => 'noopener noreferrer',
        ],
        'btnText'   => $view['translator']->trans('marketplace.package.issue.tracker'),
        'iconClass' => 'fa fa-question',
    ];
}

if ($view['security']->isGranted(MarketplacePermissions::CAN_INSTALL_PACKAGES)) {
    $buttons[] = [
        'attr' => [
            'data-toggle'      => 'confirmation',
            'data-message'     => $view['translator']->trans('marketplace.install.coming.soon'),
            'data-cancel-text' => $view['translator']->trans('mautic.core.close'),
        ],
        'btnText'   => $view['translator']->trans('mautic.core.theme.install'),
        'iconClass' => 'fa fa-download',
    ];
}

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        ['customButtons' => $buttons]
    )
);
?>

<div class="col-md-9">
    <?php if ($packageDetail->getPackageBase()->getDescription()) : ?>
    <div class="bg-auto">
        <div class="pr-md pl-md pt-lg pb-lg">
            <div class="box-layout">
                <div class="col-xs-10">
                    <div class="text-muted"><?php echo $view->escape($packageDetail->getPackageBase()->getDescription()); ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="panel">
    <div class="panel-heading">
        <div class="panel-title"><?php echo $view['translator']->trans('Latest Stable Version'); ?></div>
    </div>
    <table class="table table-bordered table-striped mb-0">
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.version'); ?></th>
            <td>
                <?php if (!empty($latestVersionException)) : ?>
                    <div class="text-danger">
                        <?php echo $view->escape($latestVersionException->getMessage()); ?>
                    </div>
                <?php else : ?>
                    <a href="<?php echo $view->escape($packageDetail->getPackageBase()->getRepository()); ?>/releases/tag/<?php echo $view->escape($latestVersion->getVersion()); ?>" target="_blank" rel="noopener noreferrer" >
                        <strong><?php echo $view->escape($latestVersion->getVersion()); ?></strong>
                    </a>
                <?php endif; ?>
            </td>
        </tr>
        <?php if (!empty($latestVersion)) : ?>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.version.release.date'); ?></th>
            <td title="<?php echo $view['date']->toText($latestVersion->getTime()); ?>">
                <?php echo $view['date']->toDate($latestVersion->getTime()); ?>
            </td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.license'); ?></th>
            <td><?php echo $view->escape(implode(', ', $latestVersion->getLicense())); ?></td>
        </tr>
        <?php if ($latestVersion->getHomepage()) : ?>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.homepage'); ?></th>
            <td><?php echo $view->escape($latestVersion->getHomepage()); ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <th>
                <?php echo $view['translator']->trans('marketplace.package.required.packages'); ?>
                (<?php echo count($latestVersion->getRequire()); ?>)
            </th>
            <td><?php echo $view->escape(implode(', ', array_keys($latestVersion->getRequire()))); ?></td>
        </tr>
        <?php endif; ?>
    </table>
    </div>

    <div class="panel">
    <div class="panel-heading">
        <div class="panel-title"><?php echo $view['translator']->trans('marketplace.package.all.versions'); ?></div>
    </div>
    <table class="table table-bordered table-striped mb-0">
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.version'); ?></th>
            <th><?php echo $view['translator']->trans('marketplace.package.version.release.date'); ?></th>
        </tr>
        <?php foreach ($packageDetail->getVersions()->sortByLatest() as $version) : ?>
        <tr>
            <td>
                <a href="<?php echo $view->escape($packageDetail->getPackageBase()->getRepository()); ?>/releases/tag/<?php echo $view->escape($version->getVersion()); ?>" target="_blank" rel="noopener noreferrer" >
                    <?php echo $view->escape($version->getVersion()); ?>
                </a>
            </td>
            <td title="<?php echo $view['date']->toText($version->getTime()); ?>">
                <?php echo $view['date']->toDate($version->getTime()); ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>

<div class="col-md-3 panel pb-lg">
    <h3 class="pt-lg pb-lg pl-sm"><?php echo $view['translator']->trans('marketplace.package.maintainers'); ?></h3>
    <?php foreach ($packageDetail->getMaintainers() as $maintainer) : ?>
        <div class="box-layout">
            <div class="col-xs-3 va-m">
                <div class="panel-body">
                    <span class="img-wrapper img-rounded">
                        <img class="img" src="<?php echo $view->escape($maintainer->getAvatar()); ?>">
                    </span>
                </div>
            </div>
            <div class="col-xs-9 va-t">
                <div class="panel-body">
                    <h4 class="fw-sb mb-xs ellipsis">
                        <?php echo $view->escape(ucfirst($maintainer->getName())); ?>
                    </h4>
                    <a href="https://packagist.org/packages/<?php echo $view->escape($maintainer->getName()); ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo $view['translator']->trans('marketplace.other.packages', ['%name%' => $maintainer->getName()]); ?>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <h3 class="pt-lg pb-lg pl-sm"><?php echo $view['translator']->trans('marketplace.package.github.info'); ?></h3>
    <table class="table mb-0">
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.repository'); ?></th>
            <td>
                <a href="<?php echo $view->escape($packageDetail->getPackageBase()->getRepository()); ?>" target="_blank" rel="noopener noreferrer" >
                    <?php echo $view->escape($packageDetail->getPackageBase()->getName()); ?>
                </a>
            </td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.github.stars'); ?></th>
            <td><?php echo $view->escape($packageDetail->getGithubInfo()->getStars()); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.github.watchers'); ?></th>
            <td><?php echo $view->escape($packageDetail->getGithubInfo()->getWatchers()); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.github.forks'); ?></th>
            <td><?php echo $view->escape($packageDetail->getGithubInfo()->getForks()); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.github.open.issues'); ?></th>
            <td><?php echo $view->escape($packageDetail->getGithubInfo()->getOpenIssues()); ?></td>
        </tr>
    </table>

    <h3 class="pt-lg pb-lg pl-sm"><?php echo $view['translator']->trans('marketplace.package.packagist.info'); ?></h3>
    <table class="table mb-0">
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.repository'); ?></th>
            <td>
                <a href="<?php echo $view->escape($packageDetail->getPackageBase()->getUrl()); ?>" target="_blank" rel="noopener noreferrer" >
                    <?php echo $view->escape($packageDetail->getPackageBase()->getName()); ?>
                </a>
            </td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.total.downloads'); ?></th>
            <td><?php echo $view->escape($packageDetail->getPackageBase()->getDownloads()); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.monthly.downloads'); ?></th>
            <td><?php echo $view->escape($packageDetail->getMonthlyDownloads()); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.daily.downloads'); ?></th>
            <td><?php echo $view->escape($packageDetail->getDailyDownloads()); ?></td>
        </tr>
        <tr>
            <th><?php echo $view['translator']->trans('marketplace.package.create.date'); ?></th>
            <td title="<?php echo $view['date']->toText($packageDetail->getTime()); ?>">
                <?php echo $view['date']->toDate($packageDetail->getTime()); ?>
            </td>
        </tr>
    </table>
</div>


