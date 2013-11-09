<?php
namespace {
    include getenv('ZF2_PATH').'/vendor/autoload.php';
    chdir(dirname(__DIR__));
    error_reporting(E_ERROR | E_WARNING | E_PARSE | E_RECOVERABLE_ERROR);

    // http://www.php.net/manual/ja/stream.streamwrapper.example-1.php
    class VariableStream {
        public static $variables = array();
        private $position;
        private $varname;

        function stream_open($path, $mode, $options, &$opened_path)
        {
            $url = parse_url($path);
            $this->varname = $url["host"];
            $this->position = 0;

            return true;
        }

        function stream_read($count)
        {
            $ret = substr(self::$variables[$this->varname], $this->position, $count);
            $this->position += strlen($ret);
            return $ret;
        }

        function stream_write($data)
        {
            $left = substr(self::$variables[$this->varname], 0, $this->position);
            $right = substr(self::$variables[$this->varname], $this->position + strlen($data));
            self::$variables[$this->varname] = $left . $data . $right;
            $this->position += strlen($data);
            return strlen($data);
        }

        function stream_tell()
        {
            return $this->position;
        }

        function stream_eof()
        {
            return $this->position >= strlen(self::$variables[$this->varname]);
        }

        function stream_seek($offset, $whence)
        {
            switch ($whence) {
                case SEEK_SET:
                if ($offset < strlen(self::$variables[$this->varname]) && $offset >= 0) {
                    $this->position = $offset;
                    return true;
                } else {
                 return false;
                }
                break;
                case SEEK_CUR:
                if ($offset >= 0) {
                    $this->position += $offset;
                    return true;
                } else {
                    return false;
                }
                break;

                case SEEK_END:
                if (strlen(self::$variables[$this->varname]) + $offset >= 0) {
                    $this->position = strlen(self::$variables[$this->varname]) + $offset;
                    return true;
                } else {
                    return false;
                }
                break;

                default:
                return false;
            }
        }

        function stream_metadata($path, $option, $var) 
        {
            if($option == STREAM_META_TOUCH) {
                $url = parse_url($path);
                $varname = $url["host"];
                if(!isset(self::$variables[$varname])) {
                    self::$variables[$varname] = '';
                }
                return true;
            }
            return false;
        }

        public function stream_stat()
        {
            return false;
            //throw new Exception('');
        }
    }

    // register templates dynamic!
    VariableStream::$variables['layout_layout'] = <<<'LAYOUT'
<?php echo $this->doctype(); ?>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <?php echo $this->headTitle('Zend Skeleton Left')->setSeparator(' - ')->setAutoEscape(false) ?>
        <?php echo $this->headMeta()->appendName('viewport', 'width=device-width, initial-scale=1.0') ?>
    </head>
    <body>
        <header></header>
        <div role="main">
            <?php echo $this->content; ?>
        </div>
        <footer></footer>
        <?php echo $this->inlineScript(); ?>
    </body>
</html>
LAYOUT;
    VariableStream::$variables['application_index_index'] = '<h1>It works!</h1>';

    VariableStream::$variables['error_404'] = <<<'ERROR404'
<h1>A 404 error occurred</h1>
<h2><?php echo $this->message ?></h2>

<?php if (isset($this->reason) && $this->reason): ?>

    <?php
    $reasonMessage= '';
    switch ($this->reason) {
        case 'error-controller-cannot-dispatch':
            $reasonMessage = 'The requested controller was unable to dispatch the request.';
            break;
        case 'error-controller-not-found':
            $reasonMessage = 'The requested controller could not be mapped to an existing controller class.';
            break;
        case 'error-controller-invalid':
            $reasonMessage = 'The requested controller was not dispatchable.';
            break;
        case 'error-router-no-match':
            $reasonMessage = 'The requested URL could not be matched by routing.';
            break;
        default:
            $reasonMessage = 'We cannot determine at this time why a 404 was generated.';
            break;
    }
    ?>

    <p><?php echo $reasonMessage ?></p>

<?php endif ?>

<?php if (isset($this->controller) && $this->controller): ?>

    <dl>
        <dt>Controller</dt>
        <dd><?php echo $this->escapeHtml($this->controller) ?>
            <?php
            if (isset($this->controller_class)
                && $this->controller_class
                && $this->controller_class != $this->controller
            ) {
                echo '(' . sprintf('resolves to %s', $this->escapeHtml($this->controller_class)) . ')';
            }
            ?>
        </dd>
    </dl>

<?php endif ?>

<?php if (isset($this->display_exceptions) && $this->display_exceptions): ?>

    <?php if(isset($this->exception) && $this->exception instanceof Exception): ?>
        <hr/>
        <h2>Additional information:</h2>
        <h3><?php echo get_class($this->exception); ?></h3>
        <dl>
            <dt>File:</dt>
            <dd>
                <pre class="prettyprint linenums"><?php echo $this->exception->getFile() ?>:<?php echo $this->exception->getLine() ?></pre>
            </dd>
            <dt>Message:</dt>
            <dd>
                <pre class="prettyprint linenums"><?php echo $this->exception->getMessage() ?></pre>
            </dd>
            <dt>Stack trace:</dt>
            <dd>
                <pre class="prettyprint linenums"><?php echo $this->exception->getTraceAsString() ?></pre>
            </dd>
        </dl>
        <?php
        $e = $this->exception->getPrevious();
        if ($e) :
            ?>
            <hr/>
            <h2>Previous exceptions:</h2>
            <ul class="unstyled">
                <?php while($e) : ?>
                    <li>
                        <h3><?php echo get_class($e); ?></h3>
                        <dl>
                            <dt>File:</dt>
                            <dd>
                                <pre class="prettyprint linenums"><?php echo $e->getFile() ?>:<?php echo $e->getLine() ?></pre>
                            </dd>
                            <dt>Message:</dt>
                            <dd>
                                <pre class="prettyprint linenums"><?php echo $e->getMessage() ?></pre>
                            </dd>
                            <dt>Stack trace:</dt>
                            <dd>
                                <pre class="prettyprint linenums"><?php echo $e->getTraceAsString() ?></pre>
                            </dd>
                        </dl>
                    </li>
                    <?php
                    $e = $e->getPrevious();
                endwhile;
                ?>
            </ul>
        <?php endif; ?>

    <?php else: ?>

        <h3>No Exception available</h3>

    <?php endif ?>

<?php endif ?>
ERROR404;

    VariableStream::$variables['error_index'] = <<<'ERRORINDEX'
<h1>An error occurred</h1>
<h2><?php echo $this->message ?></h2>

<?php if (isset($this->display_exceptions) && $this->display_exceptions): ?>

    <?php if(isset($this->exception) && $this->exception instanceof Exception): ?>
        <hr/>
        <h2>Additional information:</h2>
        <h3><?php echo get_class($this->exception); ?></h3>
        <dl>
            <dt>File:</dt>
            <dd>
                <pre class="prettyprint linenums"><?php echo $this->exception->getFile() ?>:<?php echo $this->exception->getLine() ?></pre>
            </dd>
            <dt>Message:</dt>
            <dd>
                <pre class="prettyprint linenums"><?php echo $this->exception->getMessage() ?></pre>
            </dd>
            <dt>Stack trace:</dt>
            <dd>
                <pre class="prettyprint linenums"><?php echo $this->exception->getTraceAsString() ?></pre>
            </dd>
        </dl>
        <?php
        $e = $this->exception->getPrevious();
        if ($e) :
            ?>
            <hr/>
            <h2>Previous exceptions:</h2>
            <ul class="unstyled">
                <?php while($e) : ?>
                    <li>
                        <h3><?php echo get_class($e); ?></h3>
                        <dl>
                            <dt>File:</dt>
                            <dd>
                                <pre class="prettyprint linenums"><?php echo $e->getFile() ?>:<?php echo $e->getLine() ?></pre>
                            </dd>
                            <dt>Message:</dt>
                            <dd>
                                <pre class="prettyprint linenums"><?php echo $e->getMessage() ?></pre>
                            </dd>
                            <dt>Stack trace:</dt>
                            <dd>
                                <pre class="prettyprint linenums"><?php echo $e->getTraceAsString() ?></pre>
                            </dd>
                        </dl>
                    </li>
                    <?php
                    $e = $e->getPrevious();
                endwhile;
                ?>
            </ul>
        <?php endif; ?>

    <?php else: ?>

        <h3>No Exception available</h3>

    <?php endif ?>

<?php endif ?>
ERRORINDEX;

    stream_wrapper_register("var", "VariableStream");
}

namespace Application {
    class Module
    {
        public function getConfig()
        {
            return array(
                'router' => array(
                    'routes' => array(
                        'home' => array(
                            'type' => 'Literal',
                            'options' => array(
                                'route'    => '/',
                                'defaults' => array(
                                    'controller' => 'Application\Controller\IndexController',
                                    'action'     => 'index',
                                ),
                            ),
                        ),
                    )
                ),
                'controllers' => array(
                    'invokables' => array(
                        'Application\Controller\IndexController' => 'Application\Controller\IndexController'
                    ),
                ),
                'view_manager' => array(
                    'display_not_found_reason' => true,
                    'display_exceptions'       => true,
                    'doctype'                  => 'HTML5',
                    'not_found_template'       => 'error/404',
                    'exception_template'       => 'error/index',
                    'template_map' => array(
                        'layout/layout' => 'var://layout_layout',
                        'application/index/index' => 'var://application_index_index',
                        'error/404'               => 'var://error_404',
                        'error/index'             => 'var://error_index',
                    ),
                    //'template_path_stack' => array(
                    //    __DIR__ . '/../view',
                    //),
                )
            );
        }

    }
}

namespace Application\Controller {
    use Zend\Mvc\Controller\AbstractActionController;
    use Zend\View\Model\ViewModel;

    class IndexController extends AbstractActionController
    {
        public function indexAction()
        {
            return new ViewModel;
        }
    }
}


namespace {

Zend\Mvc\Application::init(array(
    'modules' => array(
        'Application',
    ),

    'module_listener_options' => array(
        'module_paths' => array(
            './module',
            './vendor',
        ),

        'config_glob_paths' => array(
            'config/autoload/{,*.}{global,local}.php',
        ),

        /**
         * Can be disabled in production
         */
        'check_dependencies' => true,

        /**
         * Configure cache
         */
        //'config_cache_enabled' => true,
        //'config_cache_key' => 'config_cache',
        //'module_map_cache_enabled' => true,
        //'module_map_cache_key' => 'module_map_cache',
        //'cache_dir' => __DIR__ . '/../data/cache',
    )
))->run();
}

