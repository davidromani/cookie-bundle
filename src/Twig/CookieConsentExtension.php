<?php

namespace Eveltic\CookieBundle\Twig;

use Twig\Environment;
use Twig\TwigFunction;
use Symfony\Component\Asset\Packages;
use Twig\Extension\AbstractExtension;
use Eveltic\CookieBundle\Service\CookieConsentService;

/**
 * Twig extension to provide functions for including cookie consent assets in templates.
 */
class CookieConsentExtension extends AbstractExtension
{
    /**
     * Constructor for the CookieConsentExtension class.
     *
     * @param CookieConsentService $cookieConsentService 
     *        Service responsible for handling cookie consent logic, including checking and storing consent data.
     *
     * @param Environment $twig 
     *        The Twig environment used to render templates.
     *
     * @param Packages $packages 
     *        Service for managing and generating URLs for assets (e.g., CSS, JavaScript files).
     */
    public function __construct(
        private CookieConsentService $cookieConsentService,
        private Environment $twig,
        private Packages $packages
    ) {}

    /**
     * Registers the custom Twig functions provided by the CookieConsentExtension.
     *
     * @return array 
     *        An array of TwigFunction objects representing the custom functions available in Twig templates.
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('ev_cookie_render', [$this, 'renderCookieConsent'], ['is_safe' => ['html']]),
            new TwigFunction('ev_cookie_is_category_accepted', [$this, 'isCookieCategoryAccepted']),
            new TwigFunction('ev_cookie_show_cookie_banner', [$this, 'shouldShowCookieBanner']),
            new TwigFunction('ev_cookie_include_js', [$this, 'includeCookieConsentJs'], ['is_safe' => ['html']]),
            new TwigFunction('ev_cookie_include_css', [$this, 'includeCookieConsentCss'], ['is_safe' => ['html']]),
            new TwigFunction('ev_cookie_include_assets', [$this, 'includeCookieConsentAssets'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Renders the cookie consent banner using Twig.
     *
     * @return string 
     *        The rendered HTML content for the cookie consent banner.
     */
    public function renderCookieConsent(): string
    {
        $categories = $this->cookieConsentService->getCategories();
        $themeMode = $this->cookieConsentService->getThemeMode();
        $consentDateTime = $this->cookieConsentService->getConsentDateTime();
        $consentExpiration = $this->cookieConsentService->getConsentExpiration();
        $consentUuid = $this->cookieConsentService->getConsentUuid();
        $consentCategories = $this->cookieConsentService->getConsentCategories();
        $consentVersion = $this->cookieConsentService->getConsentVersion();

        return $this->twig->render('@EvelticCookie/cookies/consent.html.twig', [
            'categories' => $categories, 
            'theme_mode' => $themeMode, 
            'consentDateTime' => $consentDateTime,
            'consentExpiration' => $consentExpiration,
            'consentUuid' => $consentUuid,
            'consentCategories' => $consentCategories,
            'consentVersion' => $consentVersion,
            ]);
    }

    /**
     * Checks if the specified cookie categories are accepted by the user.
     *
     * @param array $categories 
     *        An array of cookie category names to check.
     *
     * @return bool 
     *        Returns true if all specified categories are accepted by the user; otherwise, false.
     */
    public function isCookieCategoryAccepted(string|array $categories): bool
    {
        return $this->cookieConsentService->isCategoryAccepted($categories);
    }

    /**
     * Determines whether the cookie consent banner should be displayed.
     *
     * @return bool 
     *        Returns true if the consent banner should be shown (i.e., the user has not yet given consent); otherwise, false.
     */
    public function shouldShowCookieBanner(): bool
    {
        return !$this->cookieConsentService->isConsentGiven();
    }

    /**
     * Returns the HTML tag to include the cookie consent JavaScript file.
     *
     * @return string
     */
    public function includeCookieConsentJs(): string
    {
        $jsPath = $this->packages->getUrl('bundles/evelticcookie/js/cookie-consent.js');
        return sprintf('<script src="%s"></script>', $jsPath);
    }

    /**
     * Returns the HTML tag to include the cookie consent CSS file.
     *
     * @return string
     */
    public function includeCookieConsentCss(): string
    {
        $cssPath = $this->packages->getUrl('bundles/evelticcookie/css/cookie-consent.css');
        return sprintf('<link rel="stylesheet" href="%s" />', $cssPath);
    }

    /**
     * Returns the HTML tags to include both the cookie consent CSS and JavaScript files.
     *
     * @return string
     */
    public function includeCookieConsentAssets(): string
    {
        return $this->includeCookieConsentCss() . "\n" . $this->includeCookieConsentJs();
    }
}
