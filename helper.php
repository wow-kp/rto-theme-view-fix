<?php

use App\Enums\Account as EnumsAccount;
use App\Enums\IntegrationSettingType;
use App\Exceptions\InvalidPhoneNumberException;
use App\Models\Account;
use App\Models\Cart;
use App\Models\CustomLeadDeleteReason;
use App\Models\CustomLeadStatus;
use App\Models\IntegrationSetting;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\ListCreator;
use App\Models\ListManager;
use App\Models\Store;
use App\Models\VoipCallHistory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;
use libphonenumber\PhoneNumberFormat;

if (!function_exists('theme')) {
    /**
     * @param $page
     * @return ?string
     *
     * @phpstan-return ?view-string
     */
    function theme($page): ?string
    {
        $account = getAccount();
        if (session('theme') == '' || session('theme') != $account?->getThemeAttribute()) {
            $theme_name = $account?->getThemeAttribute();
            session(['theme' => $theme_name]);
        }

        // first we look for the file in frontend.themes.{account_id}.{page}
        $view = 'frontend.themes.' . $account?->id . '.' . $page;

        if (view()->exists($view)) {
            return $view;
        }

        // then we look for the file in frontend.themes.{theme_slug}.{page}
        $theme_name = session('theme');

        $theme = 'frontend.themes.' . $theme_name;

        $view = $theme . '.' . $page;

        if (view()->exists($view)) {
            return $view;
        }

        // last we fall back to the default theme
        $view = 'frontend.themes.default.' . $page;

        if (view()->exists($view)) {
            return $view;
        }

        return null;
    }
}

if (!function_exists('getImageForAccount')) {
    function getImageForAccount($folder_name, $filename): string
    {
        return asset('images/uploads/' . getAccount()->id . '/' . $folder_name . '/' . $filename);
    }
}
if (!function_exists('getAccount')) {
    /**
     * @param bool $refresh
     * @return \App\Models\Account|null
     */
    function getAccount(bool $refresh = false, bool $reset = false): ?Account
    {
        static $account = null;
        static $is_user = false;

        if ($reset) {
            $account = null;
            $is_user = false;
        }

        if ($account && !$refresh && ($is_user || !auth()->check())) {
            return $account;
        }

        if (auth()->user()?->account) {
            $is_user = true;

            return $account = auth()->user()->account->fresh();
        }

        if (!request()->httpHost()) {
            return null;
        }

        return $account = Account::findDomain(request()->httpHost());
    }
}

if (!function_exists('getTimeZone')) {
    function getTimeZone(): string
    {
        if (auth()->check()) {
            $account = auth()->user()->account;
            if ($account->timezone) {
                return $account->timezone;
            }
        }

        return config('app.fallback_timezone');
    }
}

if (!function_exists('getThemeAttributes')) {
    function getThemeAttributes(): array
    {
        if (getAccount()->theme_id == 4) {
            return $mikes_attributes = [
                'homepage_category', 'title', 'subtitle', 'image2', 'homepage_promise_text_1',
                'homepage_promise_text_1', 'homepage_promise_text_2', 'homepage_promise_text_3',
                'homepage_promise_text_4', 'homepage_promise', 'homepage_tagline', 'homepage_box_3_subtitle',
                'homepage_box_3_subtitle', 'homepage_box_3_details', 'homepage_box_3', 'homepage_product_1_price',
                'homepage_product_1_pricefor', 'homepage_product_1_description', 'homepage_product_1_url', 'homepage_product_2_price',
                'homepage_product_2_pricefor', 'homepage_product_2_description', 'homepage_product_2_url', 'homepage_product_3_price',
                'homepage_product_3_pricefor', 'homepage_product_3_description', 'homepage_product_3_url', 'homepage_product_4_price',
                'homepage_product_4_pricefor', 'homepage_product_4_description', 'homepage_product_4_url', 'homepage_brand_1_url',
                'homepage_brand_1', 'homepage_brand_2_url', 'homepage_brand_2', 'homepage_brand_3_url', 'homepage_brand_3',
                'homepage_brand_4_url', 'homepage_brand_4', 'homepage_brand_5_url', 'homepage_brand_5',
                'ourStoryTitle', 'ourStoryDetails', 'ourStoryLink', 'ourStoryLinktext', 'ourStoryImage', 'store_category_id',
                'homepage_box_7', 'homepage_box_6', 'homepage_box_5', 'homepage_box_4', 'homepage_product_6', 'homepage_product_5',
                'homepage_product_4', 'homepage_product_3', 'homepage_product_2', 'homepage_product_1', 'homepage_box_2',
                'homepage_box_1', 'background', 'image', 'homepage_box_7_categoryName', 'homepage_box_7_categoryLink',
                'homepage_box_7_linktext', 'homepage_box_7_pricefor', 'homepage_box_7_link', 'homepage_box_7_price',
                'homepage_box_6_categoryLink', 'homepage_box_7_productName', 'homepage_box_6_categoryName',
                'homepage_box_6_linktext', 'homepage_box_6_link', 'homepage_box_6_pricefor', 'homepage_box_6_productName', 'homepage_box_6_price',
                'homepage_box_5_categoryLink', 'homepage_box_5_categoryName', 'homepage_box_5_linktext', 'homepage_box_5_pricefor',
                'homepage_box_5_link', 'homepage_box_5_price', 'homepage_box_5_productName', 'homepage_box_4_categoryLink',
                'homepage_box_4_linktext', 'homepage_box_4_categoryName', 'homepage_box_4_pricefor', 'homepage_box_4_link',
                'homepage_box_4_price', 'homepage_product_6_url', 'homepage_box_4_productName', 'homepage_product_5_url',
                'homepage_product_6_category', 'homepage_product_4_url', 'homepage_product_5_category', 'homepage_product_4_category',
                'homepage_product_3_category', 'homepage_product_2_url', 'homepage_product_2_category', 'homepage_product_1_url',
                'homepage_product_1_category', 'homepage_box_3_link', 'homepage_box_3_linktext', 'homepage_box_3_title',
                'homepage_box_2_link', 'header_text', 'homepage_box_2_pricefor', 'homepage_box_2_price', 'homepage_box_2_linktext',
                'homepage_box_2_title', 'homepage_box_1_link', 'homepage_box_1_pricefor', 'homepage_box_1_price', 'homepage_box_1_linktext',
                'homepage_box_1_title', 'homepage_category', 'linktext', 'link', 'header_text_link',
            ];
        } elseif (getAccount()->theme_id == 5) {
            return $majik_attributes = [
                'homepage_category', 'title', 'subtitle', 'image2', 'homepage_promise_text_1',
                'homepage_promise_text_1', 'homepage_promise_text_2', 'homepage_promise_text_3',
                'homepage_promise_text_4', 'homepage_promise', 'homepage_tagline', 'homepage_box_3_subtitle',
                'homepage_box_3_subtitle', 'homepage_box_3_details', 'homepage_box_3', 'homepage_product_1_price',
                'homepage_product_1_pricefor', 'homepage_product_1_description', 'homepage_product_1_url', 'homepage_product_2_price',
                'homepage_product_2_pricefor', 'homepage_product_2_description', 'homepage_product_2_url', 'homepage_product_3_price',
                'homepage_product_3_pricefor', 'homepage_product_3_description', 'homepage_product_3_url', 'homepage_product_4_price',
                'homepage_product_4_pricefor', 'homepage_product_4_description', 'homepage_product_4_url', 'homepage_brand_1_url',
                'homepage_brand_1', 'homepage_brand_2_url', 'homepage_brand_2', 'homepage_brand_3_url', 'homepage_brand_3',
                'homepage_brand_4_url', 'homepage_brand_4', 'homepage_brand_5_url', 'homepage_brand_5',
                'ourStoryTitle', 'ourStoryDetails', 'ourStoryLink', 'ourStoryLinktext', 'ourStoryImage', 'store_category_id',
                'homepage_box_7', 'homepage_box_6', 'homepage_box_5', 'homepage_box_4', 'homepage_product_6', 'homepage_product_5',
                'homepage_product_4', 'homepage_product_3', 'homepage_product_2', 'homepage_product_1', 'homepage_box_2',
                'homepage_box_1', 'background', 'image', 'homepage_box_7_categoryName', 'homepage_box_7_categoryLink',
                'homepage_box_7_linktext', 'homepage_box_7_pricefor', 'homepage_box_7_link', 'homepage_box_7_price',
                'homepage_box_6_categoryLink', 'homepage_box_7_productName', 'homepage_box_6_categoryName',
                'homepage_box_6_linktext', 'homepage_box_6_link', 'homepage_box_6_pricefor', 'homepage_box_6_productName', 'homepage_box_6_price',
                'homepage_box_5_categoryLink', 'homepage_box_5_categoryName', 'homepage_box_5_linktext', 'homepage_box_5_pricefor',
                'homepage_box_5_link', 'homepage_box_5_price', 'homepage_box_5_productName', 'homepage_box_4_categoryLink',
                'homepage_box_4_linktext', 'homepage_box_4_categoryName', 'homepage_box_4_pricefor', 'homepage_box_4_link',
                'homepage_box_4_price', 'homepage_product_6_url', 'homepage_box_4_productName', 'homepage_product_5_url',
                'homepage_product_6_category', 'homepage_product_4_url', 'homepage_product_5_category', 'homepage_product_4_category',
                'homepage_product_3_category', 'homepage_product_2_url', 'homepage_product_2_category', 'homepage_product_1_url',
                'homepage_product_1_category', 'homepage_box_3_link', 'homepage_box_3_linktext', 'homepage_box_3_title',
                'homepage_box_2_link', 'header_text', 'homepage_box_2_pricefor', 'homepage_box_2_price', 'homepage_box_2_linktext',
                'homepage_box_2_title', 'homepage_box_1_link', 'homepage_box_1_pricefor', 'homepage_box_1_price', 'homepage_box_1_linktext',
                'homepage_box_1_title', 'homepage_category', 'linktext', 'link', 'header_text_link',
            ];
        } //To add a new theme place the required theme attributes here for default settings.
        else {
            return $empty = [];
        }
    }
}

if (!function_exists('urlFix')) {
    function urlFix($url)
    {
        if (str_contains($url, 'http')) {
            return $url;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        return '/' . $url;
    }
}

if (!function_exists('getCartCount')) {
    function getCartCount($include_quanties = false)
    {
        $cart = Session::get('cart_id', 0);
        if ($cart) {
            if (!$include_quanties) {
                if (Cart::where('id', $cart)->first()) {
                    return count(Cart::where('id', $cart)->first()->getItems());
                } else {
                    return 0;
                }
            } else {
                return Cart::where('id', $cart)->first()->getItemsIncludingQuantities();
            }
        }

        return 0;
    }
}

if (!function_exists('formatNumber')) {
    function formatNumber($number = ''): string
    {
        if ($number == '') {
            return '';
        }
        $number = preg_replace('/[^0-9]/', '', $number);
        if (strlen($number) == 10) {
            $number = '1' . $number;
        }
        if (strlen($number) == 11 || strlen($number) == 12) {
            return '+' . $number;
        }

        return '';
    }
}

if (!function_exists('decryptAndFormatSSN')) {
    /**
     * Decrypts and formats a Social Security Number.
     * If it doesn't contain 9 numeric characters, it returns an empty string.
     */
    function decryptAndFormatSSN(?string $number): string
    {
        if (empty($number)) {
            return '';
        }

        try {
            $number = decrypt($number);
        } catch (Exception $e) {
            return '';
        }

        if (preg_match('/^(\d{3})-?(\d{2})-?(\d{4})$/', $number, $matches)) {
            return $matches[1] . '-' . $matches[2] . '-' . $matches[3];
        }

        return '';
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency(float|string|null $value, string $currency = 'USD'): string
    {
        if (is_null($value)) {
            return '';
        }

        if (($fmt = \NumberFormatter::create('en_US', \NumberFormatter::DEFAULT_STYLE)->parse($value)) !== false) {
            $value = $fmt;
        } elseif (($fmt = \NumberFormatter::create('en_US', \NumberFormatter::CURRENCY)->parse($value)) !== false) {
            $value = $fmt;
        } elseif (($fmt = \NumberFormatter::create('en_US', \NumberFormatter::CURRENCY_ACCOUNTING)->parse($value)) !== false) {
            $value = $fmt;
        } else {
            // the value wasn't a format we can parse
            return '';
        }

        return \NumberFormatter::create('en_US', \NumberFormatter::CURRENCY)->formatCurrency($value, $currency);
    }
}
if (!function_exists('isPhoneValid')) {
    function isPhoneValid($number = ''): bool
    {
        if ($number == '' || $number == null) {
            return false;
        }
        return !ctype_alpha($number);
    }
}

if (!function_exists('getPageTitle')) {
    /**
     * @return list<?string>
     */
    function getPageTitle(): array
    {
        $urlTitleMapping = [
            'admin' => ['Dashboard'],
            'admin/dashboard' => ['Dashboard'],
            'admin/leadAdvanced/list' => ['Dashboard', 'Leads'],
            'admin/leads/create-new' => ['CRM', 'Create Lead'],
            'admin/crm/v2' => ['CRM', 'v2'],
            'admin/leadBasic' => ['CRM', 'Manage Leads (old)'],
            'admin/subscribers' => ['CRM', 'Email Signups'],
            'admin/leadAdvanced/activity_report' => ['CRM', 'Reports', 'Activity Report'],
            'admin/leadAdvanced/regional' => ['CRM', 'Reports', 'Performance Report'],
            'admin/leadAdvanced/creator' => ['CRM', 'Reports', 'By Creator'],
            'admin/leadAdvanced/region' => ['CRM', 'Reports', 'By Region'],
            'admin/leadAdvanced/month' => ['CRM', 'Reports', 'By Month'],
            'admin/leadAdvanced/monthDetailed' => ['CRM', 'Reports', 'By Month Detailed'],
            'admin/leadAdvanced/source' => ['CRM', 'Reports', 'By Source'],
            'admin/leadAdvanced/user' => ['CRM', 'Reports', 'By User'],
            'admin/leadAdvanced/sales' => ['CRM', 'Reports', 'Sales'],
            'admin/leadAdvanced/csv' => ['CRM', 'Reports', 'Store Lead Export'],
            'admin/leadAdvanced/summary' => ['CRM', 'Reports', 'Leads Summary'],
            'admin/leadAdvanced/timeReport' => ['CRM', 'Reports', 'Lead Time Report'],
            'admin/leadAdvanced/weeklyLeadsReport' => ['CRM', 'Reports', 'Weekly Lead Report'],
            'admin/leadAdvanced/matchReport' => ['CRM', 'Reports', 'Lead Match Report'],
            'admin/leadAdvanced/monthlyLeadsAdmin' => ['CRM', 'Reports', 'Company Leads by Month'],
            'admin/applications' => ['Applications'],
            'admin/forms' => ['Forms'],
            'admin/ordersList' => ['Orders'],
            'admin/training' => ['Training'],
            'admin/intranet' => ['Intranet'],
            'admin/careerApplications' => ['Careers', 'Career Applications'],
            'admin/jobPostings' => ['Careers', 'Job Postings'],
            'admin/forms/master' => ['Master Forms'],
            'admin/product' => ['Products', 'Product Listing'],
            'admin/category' => ['Products', 'Categories'],
            'admin/brand' => ['Products', 'Brands'],
            'admin/vendor' => ['Products', 'Vendor Import'],
            'admin/mobile-coupon' => ['Products', 'Mobile Coupons'],
            'admin/products/top_product_report' => ['Products', 'Reports', 'Top Selling Products By Account'],
            'admin/products/product' => ['Products', 'Reports', 'Top Products'],
            'admin/contents' => ['Content', 'Content'],
            'admin/blog' => ['Content', 'Blog'],
            'admin/news' => ['Content', 'Mobile News'],
            'admin/themeSettings' => ['Site Design', 'Theme Settings'],
            'admin/site-manager/menu-product' => ['Site Design', 'Menu Featured Products'],
            'admin/menu' => ['Site Design', 'Menu'],
            'admin/integrations/voip' => ['Site Manager', 'Integration Setting', 'Voip Integration'],
            'admin/site-manager/auto-responders' => ['Site Manager', 'Auto Responders'],
            'admin/settings' => ['Site Manager', 'Settings'],
            'admin/users' => ['Site Manager', 'Manage Users'],
            'admin/settings/text' => ['Site Manager', 'Texting Settings'],
            'admin/site-manager/push-notifications' => ['Site Manager', 'Push Notifications'],
            'admin/settings/my-account-upload' => ['Site Manager', 'My Account File Upload'],
            'admin/stores' => ['Stores'],
            'admin/ratings' => ['Site Manager', 'Ratings'],
            'admin/leadAdvanced/{leadAdvanced}' => ['CRM', 'Lead Detail'],
            'admin/leadAdvanced/promotions/{id}' => ['CRM', 'Promotion Detail'],
            'admin/leadBasic/{leadBasic}' => ['CRM', 'Lead Details Basic'],
            'admin/leadBasic/{leadBasic}/edit' => ['CRM', 'Lead Edit Basic'],
            'admin/applications/{id}' => ['Applications', 'Application Details'],
            'admin/forms/create' => ['Create New Form'],
            'admin/form_categories' => ['Form', 'Form Categories'],
            'admin/form_categories/create' => ['Form', 'Create New Category'],
            'admin/form_categories/{form_category}/edit' => ['Form', 'Edit Category'],
            'admin/training/create' => ['Training', 'Create Training'],
            'admin/training_categories' => ['Training', 'Training Categories'],
            'admin/training_categories/create' => ['Training', 'Create New Training Category'],
            'admin/training_categories/{training_category}/edit' => ['Training', 'Edit Training Category'],
            'admin/promo-code' => ['Promo Codes'],
            'admin/promo-code/create' => ['Promo Codes', 'Create New Promo Codes'],
            'admin/promo-code/{promo_code}/edit' => ['Promo Codes', 'Edit Promo Code'],
            'admin/product/{product}/edit' => ['Products', 'Edit Product'],
            'admin/brand/create' => ['Products', 'Create New Brand'],
            'admin/brand/{brand}/edit' => ['Products', 'Edit Brand'],
            'admin/vendor/inventory/{id}' => ['Products', 'View Collections'],
            'admin/vendor/collection/productsWithCategories' => ['Products', 'View Products'],
            'admin/mobile-coupon/create' => ['Products', 'Create New Mobile Coupons'],
            'admin/mobile-coupon/{mobile_coupon}/edit' => ['Products', 'Edit Mobile Coupons'],
            'admin/contents/create' => ['Content', 'Create New Content'],
            'admin/contents/{content}/edit' => ['Content', 'Edit Content'],
            'admin/blog/create' => ['Content', 'Create Blog Post'],
            'admin/blog/{blog}/edit' => ['Content', 'Edit Blog Post'],
            'admin/site-manager/auto-responders/create' => ['Site Manager', 'Create New Autoresponder'],
            'admin/site-manager/auto-responders/{auto_responder}/edit' => ['Site Manager', 'Edit Autoresponder'],
            'admin/users/create' => ['Site Manager', 'Create New User'],
            'admin/users/{user}' => ['Site Manager', 'View User'],
            'admin/users/{user}/edit' => ['Site Manager', 'Edit User'],
            'admin/stores/create' => ['Stores', 'Create New Store'],
            'admin/stores/{store}/edit' => ['Stores', 'Edit Store'],
            'admin/contacts' => ['CRM', 'Contact List'],
            'admin/contacts/{contact}' => ['Contacts', 'Contact Detail'],
            'admin/crm/contact_histories' => ['CRM', 'Contact Histories'],
            'admin/list-creators' => ['CRM', 'List Management'],
            'admin/leadAdvanced/payments' => ['CRM', 'Payment Leads'],
            'admin/textingDashboard' => ['CRM', 'Texting Dashboard'],
            'admin/call-report' => ['Voip Call Reports'],
            'admin/callHistories' => ['Voip Call History'],
            'admin/chatLogs' => ['CRM', 'Chat Logs'],
            'admin/routelist/reports' => ['Routing', 'Reports'],
            'admin/routes' => ['Routing'],
            'admin/order-inventory' => ['Order Inventory'],
            'admin/order-inventory/{id}/detail' => ['Order Inventory', 'Inventory Details'],

            'admin/tv-subscriptions' => ['Manage Subscriptions'],
            'admin/tv-subscriptions/create/{id?}' => ['Manage Subscriptions', 'TV Subscriptions'],


            'admin/home-office-contacts' => ['Home Office Contacts'],
            'admin/request-for-help' => ['Help Requests'],
            'admin/request-for-help/create' => ['Help Requests', 'New Help Request'],
            'admin/request-for-help/create/{id?}' => ['Request for Help', 'Request detail'],

            'admin/supply-types' => ['Print Materials', 'Supply Types'],
            'admin/print-supplies/orders' => ['Order Print Materials', 'Orders'],
            'admin/supply-types/createOrEdit/{id?}' => ['Print Materials', 'Supply Types', 'Supply Type Form'],

            'admin/events/calendar-view' => ['Events Calendar'],
            'admin/events/create' => ['Events Calendar', 'New Event Type'],
            'admin/events/{event}/edit' => ['Events Calendar', 'Edit'],

            'admin/inventory-transfer-requests' => ['Inventory Transfer Requests'],
            'admin/inventory-transfer-requests/{status_type?}' => ['Inventory Transfer Requests'],
            'admin/inventory-transfer-requests/closed-requests' => ['Inventory Transfer Requests'],
            'admin/inventory-transfer-requests/create' => ['Inventory Transfer Requests', 'New Transfer Request'],
            'admin/inventory-transfer-requests/{inventory_transfer_request}/edit' => ['Inventory Transfer Requests', 'Edit Transfer Request'],
            'admin/inventory-transfer-requests/{inventory_transfer_request}' => ['Inventory Transfer Requests', 'Detail'],

            'admin/print-supplies' => ['Order Print Materials'],
            'admin/print-supplies/create/{id?}' => ['Order Print Materials', 'Print Materials Form'],
            'admin/order-inventory/orders' => ['Order Inventory', 'Inventory Orders'],

            'admin/community-outreach' => ['Community Outreach Requests'],
            'admin/community-outreach/create' => ['Community Outreach Requests', 'New Request'],
            'admin/community-outreach/{community_outreach}' => ['Community Outreach Requests', 'Detail'],
            'admin/community-outreach/{community_outreach}/edit' => ['Community Outreach Requests', 'Edit'],

            'admin/instore-promo-request' => ['InStore Promotion Requests'],
            'admin/instore-promo-request/create' => ['InStore Promotion Requests', 'New Request'],
            'admin/instore-promo-request/{instore_promo_request}' => ['InStore Promotion Requests', 'Detail'],
            'admin/instore-promo-request/{instore_promo_request}/edit' => ['InStore Promotion Requests', 'Edit'],

            'admin/promo-ideas' => ['Promo Ideas'],
            'admin/promo-ideas/create' => ['Promo Ideas', 'New Idea'],
            'admin/promo-ideas/{promo_idea}' => ['Promo Ideas', 'Detail'],
            'admin/promo-ideas/{promo_idea}/edit' => ['Promo Ideas', 'Edit'],

            'admin/tire-returns' => ['Tire Return Requests'],
            'admin/tire-returns/create' => ['Tire Return Requests', 'Return Request Form'],
            'admin/tire-returns/{tire_return}' => ['Tire Return Requests', 'Return Request'],
            'admin/tire-returns/{tire_return}/edit' => ['Tire Return Requests', 'Edit Return Request'],


            'admin/jewelry-returns' => ['Jewelry Return Requests'],
            'admin/jewelry-returns/create' => ['Jewelry Return Requests', 'Return Request Form'],
            'admin/jewelry-returns/{jewelry_return}' => ['Jewelry Return Requests', 'Return Request'],
            'admin/jewelry-returns/{jewelry_return}/edit' => ['Jewelry Return Requests', 'Edit Return Request'],

            'admin/damage-reports' => ['Damage Reports'],
            'admin/damage-reports/create' => ['Damage Reports', 'Damage Report Form'],
            'admin/damage-reports/{damage_report}' => ['Damage Reports', 'Detail'],
            'admin/damage-reports/{damage_report}/edit' => ['Damage Reports', 'Edit Damage Report'],

            'admin/qr-code-products' => ['QR Code Products'],
            'admin/qr-code-products/create' => ['QR Code Products', 'Product Form'],
            'admin/qr-code-products/{tire_return}' => ['QR Code Products', 'Product'],
            'admin/qr-code-products/{tire_return}/edit' => ['QR Code Products', 'Edit Product'],

            'admin/awf-warranty-repair-reports' => ['AWF Warranty Repair Reports'],
            'admin/awf-warranty-repair-reports/create' => ['AWF Warranty Repair Reports', 'New AWF Report'],
            'admin/awf-warranty-repair-reports/{awf_warranty_repair_report}' => ['AWF Warranty Repair Reports', 'Detail'],
            'admin/awf-warranty-repair-reports/{awf_warranty_repair_report}/edit' => ['AWF Warranty Repair Reports', 'Edit Report'],

            'admin/helper-pages/r2o/leopard-mobility-claims' => ['Mobile Phone Warranty'],

            'admin/customer-transfer-requests/{customer_transfer_request}' => ['CRM', 'Customer Transfer Request'],
        ];
        return $urlTitleMapping[Route::current()->uri] ?? [];
    }
}
if (!function_exists('getPageTitleLink')) {
    function getPageTitleLink($key): string
    {
        return match ($key) {
            'CRM' => '/admin/crm/v2',
            'Dashboard' => route('admin.dashboard'),
            'Leads' => '/admin/leadAdvanced/list/',
            'Applications' => '/admin/applications/',
            'Forms' => '/admin/forms',
            'Orders', 'Training', 'Careers', 'Master Forms', 'Site Design', 'Application Details', 'Create New Form', 'Form', 'Edit Promo Code' => '',
            'Products', 'Product Listing' => '/admin/product',
            'Blog' => '/admin/blog-posts',
            'Intranet' => '/admin/intranet',
            'Content' => '/admin/contents',
            'Site Manager' => '/admin/settings',
            'Brands' => '/admin/brand',
            'Stores' => '/admin/stores',
            'Vendor Import' => '/admin/vendor',
            'Mobile Coupons' => '/admin/mobile-coupon',
            'Promo Codes' => '/admin/promo-code',
            'Contacts' => '/admin/contacts',
            'Routing' => '/admin/routes',
            'Community Outreach Requests' => '/admin/community-outreach',
            'InStore Promotion Requests' => '/admin/instore-promo-request',
            'Tire Return Requests' => '/admin/tire-returns',
            'Jewelry Return Requests' => '/admin/jewelry-returns',
            'QR Code Products' => '/admin/qr-code-products',
            'Promo Ideas' => '/admin/promo-ideas',
            'Damage Reports' => '/admin/damage-reports',
            'Inventory Transfer Requests' => '/admin/inventory-transfer-requests',
            'AWF Warranty Repair Reports' => '/admin/awf-warranty-repair-reports',
            'Order Print Materials' => '/admin/print-supplies',
            'Supply Types' => '/admin/supply-types',
            'Manage Subscriptions' => '/admin/tv-subscriptions',
            'Help Requests' => '/admin/request-for-help',
            'Events Calendar' => '/admin/events/calendar-view',
            'Order Inventory' => '/admin/order-inventory',
            'Inventory Orders' => '/admin/order-inventory/orders',
            default => '#'
        };
    }
}
function remove_empty_tags_recursive($str, $repto = null)
{
    //** Return if string not given or empty.
    if (!is_string($str) || trim($str) == '') {
        return $str;
    }

    //** Recursive empty HTML tags.
    return preg_replace('/<([^<\/>]*)>([\s]*?|(?R))<\/\1>/imsU', !is_string($repto) ? '' : $repto, $str);
}

/**
 * @param string $text
 * @return string
 */
function force_balance_tags($text)
{
    $tagstack = [];
    $stacksize = 0;
    $tagqueue = '';
    $newtext = '';
    // Known single-entity/self-closing tags.
    $single_tags = ['area', 'base', 'basefont', 'br', 'col', 'command', 'embed', 'frame', 'hr', 'img', 'input', 'isindex', 'link', 'meta', 'param', 'source', 'track', 'wbr'];
    // Tags that can be immediately nested within themselves.
    $nestable_tags = ['article', 'aside', 'blockquote', 'details', 'div', 'figure', 'object', 'q', 'section', 'span'];

    // WP bug fix for comments - in case you REALLY meant to type '< !--'.
    $text = str_replace('< !--', '<    !--', $text);
    // WP bug fix for LOVE <3 (and other situations with '<' before a number).
    $text = (string) preg_replace('#<([0-9]{1})#', '&lt;$1', $text);

    /**
     * Matches supported tags.
     *
     * To get the pattern as a string without the comments paste into a PHP
     * REPL like `php -a`.
     *
     * @see https://html.spec.whatwg.org/#elements-2
     * @see https://html.spec.whatwg.org/multipage/custom-elements.html#valid-custom-element-name
     *
     * @example
     * ~# php -a
     * php > $s = [paste copied contents of expression below including parentheses];
     * php > echo $s;
     */
    $tag_pattern = (
        '#<' // Start with an opening bracket.
        . '(/?)' // Group 1 - If it's a closing tag it'll have a leading slash.
        . '(' // Group 2 - Tag name.
            // Custom element tags have more lenient rules than HTML tag names.
            . '(?:[a-z](?:[a-z0-9._]*)-(?:[a-z0-9._-]+)+)'
                . '|'
            // Traditional tag rules approximate HTML tag names.
            . '(?:[\w:]+)'
        . ')'
        . '(?:'
            // We either immediately close the tag with its '>' and have nothing here.
            . '\s*'
            . '(/?)' // Group 3 - "attributes" for empty tag.
                . '|'
            // Or we must start with space characters to separate the tag name from the attributes (or whitespace).
            . '(\s+)' // Group 4 - Pre-attribute whitespace.
            . '([^>]*)' // Group 5 - Attributes.
        . ')'
        . '>#' // End with a closing bracket.
    );

    while (preg_match($tag_pattern, $text, $regex)) {
        $full_match = $regex[0];
        $has_leading_slash = !empty($regex[1]);
        $tag_name = $regex[2];
        $tag = strtolower($tag_name);
        $is_single_tag = in_array($tag, $single_tags, true);
        $pre_attribute_ws = $regex[4] ?? '';
        $attributes = trim($regex[5] ?? $regex[3]);
        $has_self_closer = '/' === substr($attributes, -1);

        $newtext .= $tagqueue;

        $i = strpos($text, $full_match);
        $l = strlen($full_match);

        // Clear the shifter.
        $tagqueue = '';
        if ($has_leading_slash) { // End tag.
            // If too many closing tags.
            if ($stacksize <= 0) {
                $tag = '';
                // Or close to be safe $tag = '/' . $tag.

                // If stacktop value = tag close value, then pop.
            } elseif ($tagstack[$stacksize - 1] === $tag) { // Found closing tag.
                $tag = '</' . $tag . '>'; // Close tag.
                array_pop($tagstack);
                $stacksize--;
            } else { // Closing tag not at top, search for it.
                for ($j = $stacksize - 1; $j >= 0; $j--) {
                    if ($tagstack[$j] === $tag) {
                        // Add tag to tagqueue.
                        for ($k = $stacksize - 1; $k >= $j; $k--) {
                            $tagqueue .= '</' . array_pop($tagstack) . '>';
                            $stacksize--;
                        }
                        break;
                    }
                }
                $tag = '';
            }
        } else { // Begin tag.
            if ($has_self_closer) { // If it presents itself as a self-closing tag...
                // ...but it isn't a known single-entity self-closing tag, then don't let it be treated as such
                // and immediately close it with a closing tag (the tag will encapsulate no text as a result).
                if (!$is_single_tag) {
                    $attributes = trim(substr($attributes, 0, -1)) . "></$tag";
                }
            } elseif ($is_single_tag) { // Else if it's a known single-entity tag but it doesn't close itself, do so.
                $pre_attribute_ws = ' ';
                $attributes .= '/';
            } else { // It's not a single-entity tag.
                // If the top of the stack is the same as the tag we want to push, close previous tag.
                if ($stacksize > 0 && !in_array($tag, $nestable_tags, true) && $tagstack[$stacksize - 1] === $tag) {
                    $tagqueue = '</' . array_pop($tagstack) . '>';
                    $stacksize--;
                }
                $stacksize = array_push($tagstack, $tag);
            }

            // Attributes.
            if ($has_self_closer && $is_single_tag) {
                // We need some space - avoid <br/> and prefer <br />.
                $pre_attribute_ws = ' ';
            }

            $tag = '<' . $tag . $pre_attribute_ws . $attributes . '>';
            // If already queuing a close tag, then put this tag on too.
            if (!empty($tagqueue)) {
                $tagqueue .= $tag;
                $tag = '';
            }
        }
        $newtext .= substr($text, 0, $i) . $tag;
        $text = substr($text, $i + $l);
    }

    // Clear tag queue.
    $newtext .= $tagqueue;

    // Add remaining text.
    $newtext .= $text;

    while ($x = array_pop($tagstack)) {
        $newtext .= '</' . $x . '>'; // Add remaining tags to close.
    }

    // WP fix for the bug with HTML comments.
    $newtext = str_replace('< !--', '<!--', $newtext);
    $newtext = str_replace('<    !--', '< !--', $newtext);

    return $newtext;
}

if (!function_exists('strip_ms_word_html')) {
    function strip_ms_word_html($text, $allowed_tags = '<p><b><i><sup><sub><em><strong><u><br><div>')
    {
        mb_regex_encoding('UTF-8');
        // replace MS special characters first
        $search = ['/&lsquo;/u', '/&rsquo;/u', '/&ldquo;/u', '/&rdquo;/u', '/&mdash;/u'];
        $replace = ['\'', '\'', '"', '"', '-'];
        $text = preg_replace($search, $replace, $text);
        // make sure _all_ html entities are converted to the plain ascii equivalents - it appears
        // in some MS headers, some html entities are encoded and some aren't
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        // try to strip out any C style comments first, since these, embedded in html comments, seem to
        // prevent strip_tags from removing html comments (MS Word introduced combination)
        if (mb_stripos($text, '/*') !== false) {
            $text = mb_eregi_replace('#/\*.*?\*/#s', '', $text, 'm');
        }
        // introduce a space into any arithmetic expressions that could be caught by strip_tags so that they won't be
        // '<1' becomes '< 1'(note: somewhat application specific)
        $text = preg_replace(['/<([0-9]+)/'], ['< $1'], $text);
        $text = strip_tags($text, $allowed_tags);
        // eliminate extraneous whitespace from start and end of line, or anywhere there are two or more spaces, convert it to one
        $text = preg_replace(['/^\s\s+/', '/\s\s+$/', '/\s\s+/u'], ['', '', ' '], $text);
        // strip out inline css and simplify style tags
        $search = ['#<(strong|b)[^>]*>(.*?)</(strong|b)>#isu', '#<(em|i)[^>]*>(.*?)</(em|i)>#isu', '#<u[^>]*>(.*?)</u>#isu'];
        $replace = ['<b>$2</b>', '<i>$2</i>', '<u>$1</u>'];
        $text = preg_replace($search, $replace, $text);
        // on some of the ?newer MS Word exports, where you get conditionals of the form 'if gte mso 9', etc., it appears
        // that whatever is in one of the html comments prevents strip_tags from eradicating the html comment that contains
        // some MS Style Definitions - this last bit gets rid of any leftover comments */
        $num_matches = preg_match_all('/\<!--/u', $text, $matches);
        if ($num_matches) {
            $text = preg_replace('/\<!--(.)*--\>/isu', '', $text);
        }
        $text = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $text);
        $text = preg_replace('/(<[^>]+) class=".*?"/i', '$1', $text);
        $text = preg_replace('/div>/', 'p>', $text);
        $text = preg_replace('/<br>/', '', $text);

        $text = force_balance_tags((string) $text);

        $text = htmlentities($text, null, 'utf-8');
        $text = str_replace('&nbsp;', ' ', $text);

        $text = remove_empty_tags_recursive($text);

        $text = html_entity_decode($text);

        $text = preg_replace('/<p><\/p>/', '', $text);

        $text = preg_replace('/<p[^>]*>(?:\s|&nbsp;)*<\/p>/', '', $text);

        return $text;
    }
}


if (!function_exists('getSortUrl')) {
    function getSortUrl($url, $column, $returnDirection = false): string
    {
        $url = $url . '?sort_name=' . $column;
        $direction = 'asc';
        if (request()->get('sort_name') === $column && request()->has('direction')) {
            $direction = request()->get('direction') === 'asc' ? 'desc' : 'asc';
        }
        $url = $url . '&direction=' . $direction;

        if ($returnDirection) {
            return $direction;
        }

        return $url;
    }
}

if (!function_exists('carbon_parse')) {
    function carbon_parse($date = null, $format = null): string
    {
        if ($date) {
            $date = Carbon::parse($date);
            if ($format) {
                $date = $date->format($format);
            }

            return $date;
        } else {
            return Carbon::now();
        }
    }
}

if (!function_exists('formatPhoneNumber')) {
    /**
     * @param string $phone_number
     * @param string $country
     * @return string|null
     *
     * @deprecated Use formatPhoneDB() instead.
     */
    function formatPhoneNumber(string $phone_number, string $country = 'US'): ?string
    {
        return formatPhoneDB($phone_number);
    }
}

/**
 *  return account's specific columns array to be used in vue
 */
if (!function_exists('getAccountDetails')) {
    function getAccountDetails(): ?array
    {
        $account = getAccount();

        return [
            'id' => $account->id,
            'name' => $account->name,
            'domain' => $account->domain,
            'subdomain' => $account->subdomain,
            'active' => $account->active,
            'text_number' => $account->text_number,
            'timezone' => $account->timezone,
            'hide_club_tier' => $account->hide_club_tier,
            'site_url' => $account->site_url,
        ];
    }
}

if (!function_exists('getAccountDetail')) {
    /**
     * @deprecated use getAccountDetails() instead
     */
    function getAccountDetail(): ?array
    {
        return getAccountDetails();
    }
}

/**
 *  updates lead seen count
 */
if (!function_exists('updateLeadCount')) {
    function updateLeadCount($contactId = null, $leadId = null): void
    {
        $closedStatus = LeadStatus::whereIn('name', ['Won', 'Lost', 'Out Of Area'])->pluck('id')->toArray();
        if ($contactId) {
            $leads = Lead::whereContactId($contactId)->whereNotIn('lead_status_id', $closedStatus)->get();
            foreach ($leads as $lead) {
                $lead->views = $lead->views + 1;
                $lead->save();
            }
        }
        if ($leadId) {
            $lead = Lead::whereId($leadId)->whereNotIn('lead_status_id', $closedStatus)->first();
            if ($lead) {
                $lead->views = $lead->views + 1;
                $lead->save();
            }
        }
    }
}

/**
 *  gets list names and labels
 */
if (!function_exists('getListCustomization')) {
    function getListCustomization($customLeads = false, $onlyLeads = false, $onlyUsers = false)
    {
        $baseQuery = ListManager::where('account_id', getAccount()?->id)
            ->where('enabled', 1);

        $customListArray = (clone $baseQuery)
            ->where('type', 'customization')
            ->pluck('value', 'key');

        $leads = (clone $baseQuery)
            ->where('type', 'lead')
            ->orderBy('order', 'asc')
            ->get()
            ->groupBy('under_user_list');

        $leadList = $leads->get(0, collect());
        $leadUserList = $leads->get(1, collect());
        $leadListArray = $leadList->pluck('value', 'cta');

        $prospect_list = ListCreator::where('enabled', 1)
            ->where('import_type', \App\Enums\ImportType::Prospect)
            ->where('show', 1)
            ->whereNotNull('name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($list) {
                // @todo this should not be necessary
                $existingData = $list->data ?? [];
                $list->data = array_merge($existingData, [
                    'filters' => ['target_table' => 'contacts'],
                ]);
                return $list;
            });

        return match (true) {
            $customLeads => $customListArray,
            $onlyLeads => $leadListArray,
            $onlyUsers => $leadUserList,
            default => [
                'custom_leads' => $customListArray,
                'list_leads' => $leadListArray,
                'lead_users' => $leadUserList,
                'lead_list' => $leadList,
                'prospect_list' => $prospect_list,
            ],
        };
    }
}

if (!function_exists('getStoreVoipCallsCounts')) {
    /**
     * @param Store $store
     * @param string $start | date
     * @param string $end | date
     * @param null $agentId
     * @return array => [total => 0, matched => 0, sales => 0, collections=> 0]
     */
    function getStoreVoipCallsCounts($store, $start, $end, $agentId = null): array
    {
        $query = voipCallsQuery($store, $start, $end, $agentId);

        return [
            'total' => number_format($query->clone()->count()),
            'matched' => number_format($query->clone()->matchedCalls()->count()),
            'sales' => number_format($query->clone()->salesCalls()->count()),
            'collections' => number_format($query->clone()->collectionCalls()->count()),
        ];
    }
}

if (!function_exists('voipCallsQuery')) {
    /**
     * @param $store
     * @param $start
     * @param $end
     * @param $agentId
     * @return Builder<VoipCallHistory>
     */
    function voipCallsQuery($store, $start, $end, $agentId = null): Builder
    {
        $storeIds = !is_array($store) ? [$store?->id] : $store;
        $calls = VoipCallHistory::query()->withAnyAccount()->outGoingCalls();
        if (count($storeIds) > 0) {
            $calls->whereIn('store_id', $storeIds);
        }

        // need data only for a specific user assigned lead
        if ($agentId) {
            $leadSubQuery = Lead::select('id')->where('assigned_to_user_id', $agentId);
            $calls->whereIn('lead_id', $leadSubQuery);
        }

        $calls->betweenDates($start, $end);

        return $calls;
    }
}

if (!function_exists('voipEnabled')) {
    /**
     * @return int 0 = inactive, 1 = VOIP only, 2 = VOIP and basic calls
     */
    function voipEnabled(): int
    {
        $integration = IntegrationSetting::where('type', IntegrationSettingType::VOIP)
            ->active()
            ->first();

        return $integration->is_active ?? 0;
    }
}

if (!function_exists('getUniqueContactSum')) {
    /**
     * @param array $users
     * @return int Sum of unique contacts worked
     */
    function getUniqueContactSum($users): int
    {
        $uniqueContactWorkedArray = array_column($users, 'unique_contacts_worked');
        $sum = 0;
        foreach ($uniqueContactWorkedArray as $worked) {
            $sum = $sum + count($worked ?? []);
        }
        return $sum;
    }
}

if (!function_exists('getCustomLeadStatusesAndDeleteReasons')) {

    /**
     * @return array[]
     */
    function getCustomLeadStatusesAndDeleteReasons(): array
    {
        $customLead = CustomLeadStatus::all();
        $customDeleteReason = CustomLeadDeleteReason::all();
        $customLeadList = $customLeadDeleteList = [];
        foreach ($customLead as $item) {
            $customLeadList[$item->leadStatus->name] = $item->toArray();
        }
        foreach ($customDeleteReason as $item) {
            $customLeadDeleteList[$item->default_name] = $item->toArray();
        }

        return ['custom_lead_statuses' => $customLeadList, 'custom_delete_reason' => $customLeadDeleteList];
    }
}

if (!function_exists('getV2Accounts')) {

    /**
     * @return array[]
     */
    function getV2Accounts(): array
    {
        $accounts = EnumsAccount::V2_ACCOUNTS;
        $accountArray = [];
        foreach ($accounts as $account) {
            $accountArray[] = $account->value;
        }
        return $accountArray;
    }
}



if (!function_exists('formatPhone')) {
    /**
     * Format a phone number to a specific format.
     *
     * @param string|null $number
     * @param PhoneNumberFormat $format
     * @param bool|string $throws Throw an exception if the number is invalid. If set to a string, it will be used in the exception message.
     * @return string|null
     *
     * @throws \App\Exceptions\InvalidPhoneNumberException
     */
    function formatPhone(?string $number, PhoneNumberFormat $format, bool|string $throws = false): ?string
    {
        if ($number === null) {
            if ($throws) {
                throw new InvalidPhoneNumberException(is_string($throws) ? $throws : '');
            }

            return null;
        }

        // If the number is some format of 555 555 5555, set the number to the E164 format for testing purposes.
        // When the number is already an E164 format, it will not throw an exception but will still be formattable.
        if (str_ends_with((string) preg_replace('/\D/', '', $number), '5555555555')) {
            $number = '+15555555555';
        }
        try {
            return phone($number, config('app.phone_countries'), $format);
        } catch (Exception $e) {
            if ($throws) {
                throw new InvalidPhoneNumberException(is_string($throws) ? $throws : '');
            }
        }

        return null;
    }
}

if (!function_exists('formatPhoneFrontEnd')) {
    /**
     * Formats a phone number to use on a front end page.
     *
     * @param string|null $number
     * @param bool|string $throws Throw an exception if the number is invalid. If set to a string, it will be used in the exception message.
     * @return string|null the formatted phone number, or null if invalid.
     *
     * @throws \App\Exceptions\InvalidPhoneNumberException
     */
    function formatPhoneFrontEnd(?string $number, bool|string $throws = false): ?string
    {
        return formatPhone($number, PhoneNumberFormat::NATIONAL, $throws);
    }
}

if (!function_exists('formatPhoneHref')) {
    /**
     * Formats a phone number to use as the href in an anchor tag - e.g. "tel:+1-123-456-7890".
     *
     * @param string|null $number
     * @param bool|string $throws Throw an exception if the number is invalid. If set to a string, it will be used in the exception message.
     * @return string|null the formatted phone number, or null if invalid.
     *
     * @throws \App\Exceptions\InvalidPhoneNumberException
     */
    function formatPhoneHref(?string $number, bool|string $throws = false): ?string
    {
        return formatPhone($number, PhoneNumberFormat::RFC3966, $throws);
    }
}

if (!function_exists('formatPhoneDB')) {
    /**
     * Formats a phone number to store in the database in E164 format.
     *
     * @param string|null $number
     * @param bool|string $throws Throw an exception if the number is invalid. If set to a string, it will be used in the exception message.
     * @return string|null the formatted phone number, or null if invalid.
     *
     * @throws \App\Exceptions\InvalidPhoneNumberException
     */
    function formatPhoneDB(?string $number, bool|string $throws = false): ?string
    {
        return formatPhone($number, PhoneNumberFormat::E164, $throws);
    }
}

if (!function_exists('isEmailValid')) {
    /**
     * Validate the email address.
     */
    function isEmailValid(string $email): bool
    {
        $blacklist = [
            'null', 'none', 'noemail', 'noone', 'nobody',
            'na', 'n/a', 'noeee', 'aaron123', 'aaronwd2018',
            'dc', 'dh', 'ms', 'hg',
        ];

        // Validate email address format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Check if email contains only numeric characters before @
        if (preg_match('/^\d+@/', $email)) {
            return false;
        } elseif (str_contains($email, '@aronaco.net')) {
            return false;
        } else {
            $emailParts = explode('@', $email);
            // Check if email username is equal to any blacklisted strings
            if (in_array(strtolower($emailParts[0]), $blacklist)) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('getProductQtyInCart')) {
    function getProductQtyInCart($product_id)
    {
        $cart = Session::get('cart_id', 0);
        if (Cart::where('id', $cart)->first()) {
            $items = Cart::where('id', $cart)->first()->getItems();
            foreach ($items as $item) {
                if ($item->product_id == $product_id) {
                    return $item->qty;
                }
            }
        }
        return 0;
    }
}

if (!function_exists('getCartRowId')) {
    function getCartRowId($product_id)
    {
        $cart = Session::get('cart_id', 0);
        if (Cart::where('id', $cart)->first()) {
            $items = Cart::where('id', $cart)->first()->getItems();
            foreach ($items as $item) {
                if ($item->product_id == $product_id) {
                    return $item->id;
                }
            }
        }
        return 0;
    }
}

/**
 * Format Excel date values for display
 */
if (!function_exists('formatExcelDate')) {
    /**
     * @param string $value
     * @return string
     */
    function formatExcelDate($value): string
    {
        if (empty($value)) {
            return $value;
        }

        // Check if this looks like an Excel date serial number
        if (is_numeric($value) && $value > 25569 && $value < 100000) {
            try {
                // Convert Excel serial number to readable date using PhpSpreadsheet's method
                $dateTime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject(floatval($value));
                return $dateTime->format('m/d/Y');
            } catch (\Exception $e) {
                // If conversion fails, return original value
                return strval($value);
            }
        }

        // Return original value if not a date serial number
        return $value;
    }
}

if (!function_exists('isCurrencyField')) {
    /**
     * Check if a field name indicates it's a currency field
     *
     * @param string $fieldName
     * @return bool
     */
    function isCurrencyField(string $fieldName): bool
    {
        $currencyKeywords = [
            'amount', 'price', 'cost', 'fee', 'salary', 'revenue', 'budget',
            'payment', 'total', 'sum', 'balance', 'deposit', 'charge', 'rate',
            'value', 'worth', 'money', 'cash', 'dollar', 'usd', 'eur', 'gbp',
            'currency', 'wages', 'income', 'expense', 'commission', 'bonus',
        ];

        $fieldLower = strtolower($fieldName);

        foreach ($currencyKeywords as $keyword) {
            if (str_contains($fieldLower, $keyword)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('formatCurrencyValue')) {
    /**
     * Format value with appropriate currency symbol based on field name and value content
     *
     * @param string $fieldName
     * @param bool|float|int|resource|string|null $value
     * @param string $defaultCurrency
     * @return string
     */
    function formatCurrencyValue(string $fieldName, $value, string $defaultCurrency = '$'): string
    {
        // If value is empty or null, return as is
        if (empty($value) && $value !== 0 && $value !== '0') {
            return strval($value);
        }

        // If value already has currency symbol, return as is
        if (is_string($value) && preg_match('/[\$€£¥₹₽¢]/', $value)) {
            return $value;
        }

        // If it's a currency field and value is numeric, add currency symbol
        if (isCurrencyField($fieldName) && is_numeric($value) && !str_contains((string) $value, $defaultCurrency)) {
            return $defaultCurrency . $value;
        }

        return (string) $value;
    }
}

require 'performance.php';
