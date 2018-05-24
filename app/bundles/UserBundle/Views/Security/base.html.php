<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title><?php echo $view['slots']->get('pageTitle', 'Mautic'); ?></title>
    <meta name="robots" content="noindex, nofollow" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="icon" type="image/x-icon" href="<?php echo $view['assets']->getUrl('media/images/favicon.ico') ?>" />
    <link rel="apple-touch-icon" href="<?php echo $view['assets']->getUrl('media/images/apple-touch-icon.png') ?>" />
    <?php $view['assets']->outputSystemStylesheets(); ?>
    <?php echo $view->render('MauticCoreBundle:Default:script.html.php'); ?>
    <?php $view['assets']->outputHeadDeclarations(); ?>
</head>
<body>
<section id="main" role="main">
    <div class="container" style="margin-top:100px;">
        <div class="row">
            <div class="col-lg-4 col-lg-offset-4">
                <div class="panel" name="form-login">
                    <div class="panel-body">
                        <div class="mautic-logo img-circle mb-md text-center">
                            <svg width="100%" viewBox="0 0 168 58" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2.79499 4.492H-4.01592e-06V4.24861e-09H2.79499V4.492ZM-4.01592e-06 28.8204V7.04711H2.79499V28.8204H-4.01592e-06Z" transform="translate(99.8467 4.24402)" fill="#084C72"/>
                                <path d="M17.1414 3.84089C18.0256 2.58801 19.1249 1.63395 20.4395 0.980098C21.7513 0.326243 23.218 0 24.8438 0C27.7991 0 29.9499 0.888257 31.296 2.6634C32.6393 4.43991 33.3151 7.29111 33.3151 11.2225V22.2105H30.6462V10.6481C30.6462 7.77362 30.1665 5.72569 29.2193 4.50571C28.2693 3.28299 26.719 2.67437 24.5655 2.67437C23.3263 2.67437 22.2338 2.92659 21.288 3.43788C20.3449 3.94644 19.5965 4.67157 19.0413 5.62425C18.7123 6.21917 18.4738 6.8977 18.3272 7.65436C18.1846 8.41239 18.1133 9.73244 18.1133 11.6227V22.2119H15.476V10.0203C15.476 7.5118 14.9701 5.6599 13.953 4.46459C12.9387 3.27065 11.3801 2.67437 9.28421 2.67437C8.01488 2.67437 6.90455 2.92659 5.94775 3.43788C4.99233 3.94644 4.23429 4.67157 3.68051 5.62425C3.33781 6.21917 3.09656 6.8977 2.95811 7.65436C2.82103 8.41376 2.7525 9.73381 2.7525 11.6227V22.2119H8.3665e-08V0.437274H2.7525V2.91151C3.58456 1.94786 4.56191 1.22272 5.69279 0.73336C6.81682 0.245367 8.09438 0 9.52134 0C11.1704 0 12.6755 0.348174 14.0325 1.04041C15.3951 1.73265 16.4286 2.66614 17.1414 3.84089Z" transform="translate(106.103 10.8538)" fill="#084C72"/>
                                <path d="M19.4978 18.8083C18.6274 20.0625 17.4471 21.0454 15.9639 21.7568C14.478 22.4696 12.8797 22.8274 11.1635 22.8274C7.96964 22.8274 5.31035 21.7335 3.1884 19.5499C1.06234 17.3662 5.18723e-06 14.6672 5.18723e-06 11.4651C5.18723e-06 8.20541 1.08428 5.47895 3.25558 3.2871C5.42687 1.09661 8.14235 -2.87598e-07 11.402 -2.87598e-07C13.0757 -2.87598e-07 14.6151 0.341321 16.0147 1.01985C17.4129 1.69838 18.5739 2.67985 19.4978 3.96014V0.478397H22.2284V22.2517H19.4978V18.8083ZM19.4978 11.2266C19.4978 8.91684 18.6698 6.90318 17.0208 5.18013C15.3718 3.45707 13.4705 2.59623 11.3212 2.59623C8.98674 2.59623 6.99503 3.45707 5.35285 5.18013C3.7093 6.90318 2.88683 9.03335 2.88683 11.583C2.88683 14.0106 3.70655 16.0613 5.34462 17.7281C6.97994 19.3991 8.94562 20.2325 11.243 20.2325C13.4568 20.2325 15.3869 19.3511 17.0318 17.5897C18.6781 15.8282 19.4978 13.709 19.4978 11.2266Z" transform="translate(141.772 10.8127)" fill="#084C72"/>
                                <path d="M17.1442 3.84089C18.0283 2.58801 19.1263 1.63395 20.4395 0.980098C21.7527 0.326243 23.2194 0 24.8438 0C27.8005 0 29.9526 0.888257 31.296 2.6634C32.6434 4.43991 33.3165 7.29111 33.3165 11.2225V22.2105H30.6462V10.6481C30.6462 7.77362 30.1692 5.72569 29.2193 4.50571C28.2707 3.28299 26.719 2.67437 24.5683 2.67437C23.3277 2.67437 22.2338 2.92659 21.2907 3.43788C20.3477 3.94644 19.5992 4.67157 19.0441 5.62425C18.7123 6.21917 18.4779 6.8977 18.3299 7.65436C18.1846 8.41376 18.1133 9.73381 18.1133 11.6227V22.2119H15.4801V10.0203C15.4801 7.5118 14.9715 5.6599 13.9585 4.46459C12.9373 3.26928 11.3842 2.67299 9.28283 2.67299C8.01761 2.67299 6.90592 2.92522 5.94639 3.43651C4.99096 3.94507 4.23567 4.6702 3.68051 5.62288C3.33919 6.2178 3.09519 6.89633 2.95949 7.65299C2.82104 8.41239 2.75113 9.73244 2.75113 11.6214V22.2105H0V0.437274H2.75113V2.91151C3.58044 1.94786 4.56191 1.22272 5.69142 0.73336C6.82093 0.245367 8.09575 0 9.52135 0C11.1704 0 12.6741 0.348174 14.0353 1.04041C15.3937 1.73265 16.4314 2.66614 17.1442 3.84089Z" transform="translate(4 10.8538)" fill="#084C72"/>
                                <path d="M19.4978 18.8083C18.6273 20.0625 17.4512 21.0454 15.9639 21.7568C14.4794 22.4696 12.8811 22.8274 11.1649 22.8274C7.97238 22.8274 5.31035 21.7335 3.18703 19.5499C1.06235 17.3662 -1.25497e-07 14.6672 -1.25497e-07 11.4651C-1.25497e-07 8.20541 1.08565 5.47895 3.25694 3.2871C5.42686 1.09661 8.14236 -2.87598e-07 11.4007 -2.87598e-07C13.0785 -2.87598e-07 14.6151 0.341321 16.016 1.01985C17.4142 1.69838 18.5725 2.67985 19.4978 3.96014V0.478397H22.2297V22.2517H19.4978V18.8083ZM19.4978 11.2266C19.4978 8.91684 18.674 6.90318 17.0249 5.18013C15.3732 3.45707 13.4746 2.59623 11.3225 2.59623C8.98811 2.59623 6.99639 3.45707 5.35284 5.18013C3.71067 6.90318 2.88821 9.03335 2.88821 11.583C2.88821 14.0106 3.70792 16.0613 5.34462 17.7281C6.97857 19.3991 8.94836 20.2325 11.2444 20.2325C13.4582 20.2325 15.3896 19.3511 17.0345 17.5897C18.6767 15.8282 19.4978 13.709 19.4978 11.2266Z" transform="translate(39.6687 10.8127)" fill="#084C72"/>
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M22.6604 0C22.8564 0 23.0511 0 23.2416 0C32.4806 0.600396 35.9294 12.5919 31.3853 20.3518C27.9474 26.2255 16.6921 34.446 10.4482 35.1835C-6.00235 37.1258 0.721262 16.611 5.50524 9.59537C9.53529 3.68599 15.871 0.796415 22.6604 0ZM13.6476 6.10402C9.68607 9.44732 -3.51989 30.6791 9.57505 31.4015C17.1732 31.8237 28.0859 21.3675 29.3497 14.8276C30.7287 7.68452 25.7268 2.25765 18.3013 3.77646C16.4563 4.15616 14.9663 4.99233 13.6476 6.10402Z" transform="translate(64.4163 4)" fill="#084C72"/>
                                <path d="M23.5402 21.8582C23.5402 22.4011 23.3442 22.8754 22.9645 23.2838C22.5793 23.691 22.1584 23.8952 21.6924 23.8952C20.8192 23.8952 17.2785 21.4333 11.0731 16.5081C9.27186 18.4121 7.46794 20.3175 5.66675 22.2311C4.31106 23.6032 3.35564 24.2886 2.81007 24.2886C2.26314 24.2886 1.78885 24.0816 1.38173 23.6649C0.974616 23.2509 0.771743 22.7712 0.771743 22.2283C0.771743 21.5553 1.53115 20.5396 3.04859 19.177C5.61055 16.8536 7.4556 15.0565 8.59059 13.7885C4.82921 10.0559 2.34127 6.72087 1.12814 3.77783C0.375589 3.3433 1.5478e-06 2.79088 1.5478e-06 2.11783C1.5478e-06 1.538 0.197389 1.04178 0.594912 0.623699C0.991063 0.206986 1.45438 -8.10504e-08 1.9835 -8.10504e-08C2.74839 -8.10504e-08 3.37757 0.290602 3.86556 0.871807C4.20963 1.26659 4.66609 2.08219 5.23358 3.32C6.30278 5.59684 8.25064 8.10808 11.0758 10.8482C13.2046 8.47682 14.8303 6.16982 15.9516 3.93136C16.3505 3.02117 16.7535 2.10824 17.1606 1.18571C17.5581 0.44687 18.1256 0.0781338 18.8672 0.0781338C19.4594 0.0781338 19.9488 0.2673 20.3312 0.640148C20.7164 1.01711 20.9069 1.50784 20.9069 2.10961C20.9069 4.46596 18.6095 8.35894 14.0175 13.7872C15.2306 14.8207 17.5225 16.5219 20.885 18.8878C22.6547 20.1324 23.5402 21.118 23.5402 21.8582Z" transform="translate(68.9277 9.36243)" fill="#084C72"/>
                                <path d="M35.4275 0H3.43026e-06V1.82723H35.4275V0Z" transform="translate(128.467 52.0522)" fill="#084C72"/>
                                <path d="M35.2698 0H0V1.82723H35.2698V0Z" transform="translate(4.10555 52.0522)" fill="#084C72"/>
                                <path d="M0.978728 3.95055H3.85048V3.13357H0.978728V0.826571H4.01497V4.39241e-07H-3.55576e-07V7.41311H4.17947V6.58653H0.978728V3.95055Z" transform="translate(45.9756 46.4787)" fill="#084C72"/>
                                <path d="M7.10742 4.39241e-07H5.86825L4.54958 3.58181C4.18084 4.62634 3.94095 5.39123 3.77372 6.06016H3.76276C3.59278 5.36655 3.36386 4.60304 3.0198 3.58181L1.76006 0.00959576L1.75732 4.39241e-07H0.51678L0.00137215 7.39803L2.13346e-06 7.41311H0.937604L1.13637 4.2343C1.20353 3.10341 1.25699 1.89303 1.27893 0.991064C1.46261 1.76281 1.72579 2.64421 2.13017 3.84226L3.32411 7.35965L3.32685 7.36924H4.06981L5.37752 3.77783C5.75722 2.73468 6.0629 1.81901 6.3069 0.980099H6.31512C6.30553 1.8848 6.36995 3.11575 6.42204 4.1027L6.61258 7.40077V7.41311H7.57211L7.11017 0.0123358L7.10742 4.39241e-07Z" transform="translate(51.8302 46.4787)" fill="#084C72"/>
                                <path d="M3.6901 4.39241e-07H2.51947L0.00685471 7.39665L-5.85655e-07 7.41447H1.01574L1.78336 5.09103H4.38234L5.16916 7.40488L5.1719 7.41447H6.22054L3.69284 0.0109658L3.6901 4.39241e-07ZM3.07737 0.870438L3.09656 0.94583C3.19937 1.34609 3.30492 1.76007 3.45844 2.20968L4.17536 4.31928H1.98898L2.70589 2.19871C2.83063 1.80941 2.94852 1.38722 3.07737 0.870438Z" transform="translate(60.8498 46.4787)" fill="#084C72"/>
                                <path d="M0.980099 0H0V7.41311H0.980099V0Z" transform="translate(68.8057 46.4787)" fill="#084C72"/>
                                <path d="M0.980097 4.39241e-07H-1.75696e-06V7.41311H4.13697V6.58653H0.980097V4.39241e-07Z" transform="translate(72.0846 46.4787)" fill="#084C72"/>
                                <path d="M7.10605 4.39241e-07H5.86688L4.54821 3.58181C4.17947 4.62634 3.93958 5.39123 3.77235 6.06016H3.76138C3.59141 5.36655 3.36249 4.60304 3.01843 3.58181L1.7587 0.00959576L1.75595 4.39241e-07H0.516779L0.00137102 7.39803L1.00398e-06 7.41311H0.937603L1.13636 4.2343C1.20353 3.10341 1.25699 1.89303 1.27892 0.991064C1.46261 1.76281 1.7258 2.64421 2.13017 3.84226L3.32411 7.35965L3.32685 7.36924H4.0698L5.37752 3.77783C5.75722 2.73468 6.0629 1.81901 6.3069 0.980099H6.31512C6.30553 1.8848 6.36995 3.11575 6.42204 4.1027L6.61258 7.40077V7.41311H7.57211L7.11017 0.0123358L7.10605 4.39241e-07Z" transform="translate(80.704 46.4787)" fill="#084C72"/>
                                <path d="M3.3466e-07 1.25425H0.733355L1.95472 0.0219317L1.97939 1.0249e-06H0.897851L0.016451 1.23232L3.3466e-07 1.25425Z" transform="translate(93.3658 44.8022)" fill="#084C72"/>
                                <path d="M4.48378 4.38508C4.48378 5.9176 3.86693 6.72909 2.69904 6.72909C1.61476 6.72909 0.992436 5.87511 0.992436 4.38508V4.39241e-07H2.21712e-06V4.36452C2.21712e-06 6.98406 1.45027 7.5351 2.66614 7.5351C4.47692 7.5351 5.47484 6.39325 5.47484 4.32065V0.00137045H4.48241V4.38508H4.48378ZM4.48378 4.62085C4.47418 4.82784 4.45225 5.01838 4.41935 5.19246C4.45362 5.01701 4.47418 4.8251 4.48378 4.62085ZM2.82515 6.73184C3.02391 6.72087 3.20622 6.6866 3.37071 6.62629C3.20348 6.68797 3.02117 6.72087 2.82515 6.73184ZM3.58456 6.52348C3.72712 6.44398 3.85597 6.34391 3.96563 6.22054C3.85323 6.34665 3.72438 6.44398 3.58456 6.52348ZM1.33513 7.21023C1.32005 7.20338 1.30634 7.19653 1.29126 7.1883C1.30497 7.19653 1.32005 7.20338 1.33513 7.21023ZM4.34395 7.0334C4.33984 7.03614 4.33711 7.03889 4.33436 7.04163C4.33711 7.04026 4.33984 7.03614 4.34395 7.0334ZM4.11642 6.00807C4.20278 5.871 4.27542 5.71747 4.33299 5.54201C4.27679 5.71884 4.20278 5.87237 4.11642 6.00807ZM0.270041 5.94502C0.272783 5.95324 0.276894 5.9601 0.281007 5.96969C0.276894 5.9601 0.272783 5.95324 0.270041 5.94502ZM0.53323 6.4755C0.55105 6.50292 0.570236 6.52485 0.589427 6.5509C0.570236 6.52485 0.55105 6.50292 0.53323 6.4755ZM0.877292 6.88673C0.900595 6.90866 0.925266 6.92648 0.948569 6.94567C0.923895 6.92648 0.899224 6.90866 0.877292 6.88673Z" transform="translate(90.3638 46.4787)" fill="#084C72"/>
                                <path d="M1.95334 0.0219317L1.97801 1.0249e-06H0.885516L0.0150768 1.23232L-3.84859e-06 1.25425H0.733361L1.95334 0.0219317Z" transform="translate(91.7551 44.8022)" fill="#084C72"/>
                                <path d="M4.52764 3.08971H0.981472V4.39241e-07H-1.75696e-06V7.41311H0.981472V3.95055H4.52764V7.41311H5.51734V4.39241e-07H4.52764V3.08971Z" transform="translate(98.1292 46.4787)" fill="#084C72"/>
                                <path d="M0.981471 3.95055H3.85323V3.13357H0.981471V0.826571H4.01634V4.39241e-07H-2.92827e-06V7.41311H4.18084V6.58653H0.981471V3.95055Z" transform="translate(105.937 46.4787)" fill="#084C72"/>
                                <path d="M0.981471 4.39241e-07H-2.42628e-06V7.41311H4.13835V6.58653H0.981471V4.39241e-07Z" transform="translate(111.99 46.4787)" fill="#084C72"/>
                                <path d="M4.70585 4.39241e-07L3.63939 2.04381L3.52012 2.27685C3.28024 2.74428 3.07463 3.15002 2.89643 3.56262H2.89231C2.67436 3.076 2.46327 2.63461 2.1617 2.04518L1.11991 0.00822575L1.1158 0.00137045H-5.10356e-06L2.34402 4.26583V7.41447H3.33645V4.25897L5.81205 0.0205611L5.82438 4.39241e-07H4.70585Z" transform="translate(116.203 46.4787)" fill="#084C72"/>
                                </svg>

                        </div>
                        <div id="main-panel-flash-msgs">
                            <?php echo $view->render('MauticCoreBundle:Notification:flashes.html.php'); ?>
                        </div>
                        <?php $view['slots']->output('_content'); ?>
                    </div>
                </div>
            </div>
        </div>
         <div class="row">
            <div class="col-lg-4 col-lg-offset-4 text-center text-muted">
                <?php echo $view['translator']->trans('mautic.core.copyright', ['%date%' => date('Y')]); ?>
            </div>
        </div>
    </div>
</section>
<?php echo $view['security']->getAuthenticationContent(); ?>
</body>
</html>
