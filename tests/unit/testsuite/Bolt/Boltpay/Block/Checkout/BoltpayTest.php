<?php

require_once('TestHelper.php');

class Bolt_Boltpay_Block_Checkout_BoltpayTest extends PHPUnit_Framework_TestCase
{
    private $app = null;

    private $currentMock;

    /**
     * @var $testHelper Bolt_Boltpay_TestHelper
     */
    private $testHelper = null;

    public function setUp()
    {
        $this->app = Mage::app('default');

        $this->currentMock = $this->getMockBuilder('Bolt_Boltpay_Block_Checkout_Boltpay')
            ->setMethods(array('isAdminAndUseJsInAdmin'))
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->getMock()
        ;

        $this->testHelper = new Bolt_Boltpay_TestHelper();
    }

    /**
     * @inheritdoc
     */
    public function testBuildCartData()
    {
        $autoCapture = true;
        $this->app->getStore()->setConfig('payment/boltpay/auto_capture', $autoCapture);

        // Prepare test response object
        $testBoltResponse = new stdClass();
        $testBoltResponse->token = md5('bolt');

        $result = $this->currentMock->buildCartData($testBoltResponse);

        $cartData = array(
            'orderToken' => md5('bolt'),
        );

        $this->assertEquals($cartData, $result);
    }

    /**
     * @inheritdoc
     */
    public function testBuildCartDataWithEmptyTokenField()
    {
        $autoCapture = true;
        $this->app->getStore()->setConfig('payment/boltpay/auto_capture', $autoCapture);

        // Prepare test response object
        $testBoltResponse = new stdClass();

        $result = $this->currentMock->buildCartData($testBoltResponse);

        $cartData = array(
            'orderToken'    => '',
        );

        $this->assertEquals($cartData, $result, 'Something wrong with testBuildCartDataWithEmptyTokenField()');
    }

    /**
     * @inheritdoc
     */
    public function testBuildCartDataWithoutAutoCapture()
    {
        $autoCapture = false;
        $this->app->getStore()->setConfig('payment/boltpay/auto_capture', $autoCapture);

        // Prepare test response object
        $testBoltResponse = new stdClass();
        $testBoltResponse->token = md5('bolt');

        $result = $this->currentMock->buildCartData($testBoltResponse);

        $cartData = array(
            'orderToken' => md5('bolt'),
        );

        $this->assertEquals($cartData, $result, 'Something wrong with testBuildCartDataWithoutAutoCapture()');
    }

    /**
     * @inheritdoc
     */
    public function testBuildCartDataWithApiError()
    {
        $autoCapture = true;
        $this->app->getStore()->setConfig('payment/boltpay/auto_capture', $autoCapture);

        // Prepare test response object
        $testBoltResponse = new stdClass();
        $testBoltResponse->token = md5('bolt');

        $apiErrorMessage = 'Some error from api.';
        Mage::register('bolt_api_error', $apiErrorMessage);

        $result = $this->currentMock->buildCartData($testBoltResponse);

        $cartData = array(
            'orderToken' => md5('bolt'),
            'error' => $apiErrorMessage
        );

        $this->assertEquals($cartData, $result, 'Something wrong with testBuildCartDataWithApiError()');
    }

    /**
     * @inheritdoc
     */
    public function testGetCartURL()
    {
        $expected = Mage::helper('boltpay')->getMagentoUrl('checkout/cart');

        $result = $this->currentMock->getCartUrl();

        $this->assertEquals($expected, $result);
    }

    public function testGetSelectorsCSS()
    {
        $style = '.test-selector { color: red; }';

        $this->app->getStore()->setConfig('payment/additional_options/additional_css', $style);

        $result = $this->currentMock->getAdditionalCSS();

        $this->assertEquals($style, $result);
    }

    /**
     *  Test that additional Js is present
     */
    public function testGetAdditionalJs()
    {
        $js = 'jQuery("body div").text("Hello, world.")';

        $this->app->getStore()->setConfig('payment/additional_options/additional_js', $js);

        $result = $this->currentMock->getAdditionalJs();

        $this->assertEquals($js, $result);
    }

    /**
     * @inheritdoc
     */
    public function testGetSuccessURL()
    {
        $url = 'checkout/onepage/success';
        $this->app->getStore()->setConfig('payment/advanced_settings/successpage', $url);
        $expected = Mage::helper('boltpay')->getMagentoUrl($url);

        $result = $this->currentMock->getSuccessUrl();

        $this->assertEquals($expected, $result);
    }

    /**
     * @inheritdoc
     */
    public function testBuildBoltCheckoutJavascript()
    {
        $this->currentMock = $this->getMockBuilder('Bolt_Boltpay_Block_Checkout_Boltpay')
            ->setMethods(array('buildOnCheckCallback', 'buildOnSuccessCallback', 'buildOnCloseCallback'))
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->getMock()
        ;

        $autoCapture = true;
        $this->app->getStore()->setConfig('payment/boltpay/auto_capture', $autoCapture);
        Mage::app()->getRequest()->setRouteName('checkout')->setControllerName('cart');

        $cartData = json_encode (
            array(
                'orderToken' => md5('bolt')
            )
        );

        $promiseOfCartData =
            "
            new Promise( 
                function (resolve, reject) {
                    resolve($cartData);
                }
            )
            "
        ;

        $hintData = array();

        $checkoutType = Bolt_Boltpay_Block_Checkout_Boltpay::CHECKOUT_TYPE_MULTI_PAGE;

        $this->app->getStore()->setConfig('payment/advanced_settings/check', '');
        $this->app->getStore()->setConfig('payment/advanced_settings/on_checkout_start', '');
        $this->app->getStore()->setConfig('payment/advanced_settings/on_shipping_details_complete', '');
        $this->app->getStore()->setConfig('payment/advanced_settings/on_shipping_options_complete', '');
        $this->app->getStore()->setConfig('payment/advanced_settings/on_payment_submit', '');
        $this->app->getStore()->setConfig('payment/advanced_settings/success', '');
        $this->app->getStore()->setConfig('payment/advanced_settings/close', '');

        $quote = Mage::getModel('sales/quote');
        $quote->setId(6);

        $jsonHints = json_encode($hintData, JSON_FORCE_OBJECT);
        $onSuccessCallback = 'function(transaction, callback) { console.log(test) }';

        $expected = $this->testHelper->buildCartDataJs($checkoutType, $promiseOfCartData, $quote, $jsonHints);

        $this->currentMock
            ->method('buildOnCheckCallback')
            ->will($this->returnValue(''));
        $this->currentMock
            ->method('buildOnSuccessCallback')
            ->will($this->returnValue($onSuccessCallback));
        $this->currentMock
            ->method('buildOnCloseCallback')
            ->will($this->returnValue(''));

        $result = $this->currentMock->buildBoltCheckoutJavascript($checkoutType, $quote, $hintData, $promiseOfCartData);

        $this->assertEquals(preg_replace('/\s/', '', $expected), preg_replace('/\s/', '', $result));
    }

    /**
     * @inheritdoc
     */
    public function testGetSaveOrderURL()
    {
        $expected = Mage::helper('boltpay')->getMagentoUrl('boltpay/order/save');

        $result = $this->currentMock->getSaveOrderUrl();

        $this->assertEquals($expected, $result);
    }
}