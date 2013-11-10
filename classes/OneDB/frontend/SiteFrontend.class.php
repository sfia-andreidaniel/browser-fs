<?php

    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "frontendUtils.inc.php";
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "SiteFrontend_Memcache.class.php";
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "SiteFrontend_PageStrip.inc.php";

    class PageSection {
        private $_name = '';
        private $_widgets = array();
        
        public function __construct( $sectionName ) {
            $this->_name = $sectionName;
        }
        
        public function add( $code, $comment ) {
            $this->_widgets[] = array( "code" => $code, "comment" => $comment );
        }
        
        public function __toString() {
            $out = array();
            foreach ($this->_widgets as $widget) {
                $out[] = !defined( 'ONEDB_FRONTEND_DISABLE_WIDGET_CACHE_DEBUGGING' )
                    ? ( "<!-- Begin widget: " . htmlentities( $widget['comment'], ENT_COMPAT, 'utf-8' ) . " -->" )
                    : '';
                $out[] = $widget['code'];
            }
            return implode( "\n", $out );
        }
    }

    class SiteFrontend {
        
        private $_tpl      = NULL;
        private $_sections = array();
        private $_aborted = FALSE;
        private $_stdout = '';
        
        private $_cache = NULL;
        
        private $_cfg = array(
            '_PAGE_CHARSET_' => 'utf-8',
            '_PAGE_TITLE_'   => 'New Site',
            '_PAGE_KEYWORDS_'=> '',
            '_PAGE_DESCRIPTION_' => '',
            '_PAGE_DOCTYPE_' => 'HTML',
            '_SITE_NAME_'    => 'HomePage',
            '_SITE_BASE_'    => '/',
            '_SERVER_NAME_'  => ''
        );
        
        private $_dependencies = array(
            "css"         => array(
                'head'   => array(),
                'body'   => array(),
                'inline' => array()
            ),
            "javascript"  => array(
                'head'   => array(),
                'body'   => array(),
                'inline' => array()
            )
        );
        
        public function __construct( $templatePath, $sections ) {
        
            $this->_cache = new SiteFrontend_Memcache();
            
            $this->_cfg['_SERVER_NAME_'] = $_SERVER['SERVER_NAME'];
        
            $this->_tpl = new XTemplate( $templatePath );
            foreach ($sections as $section)
                $this->_sections[ $section ] = new PageSection( $section );
        }
        
        public function assign($varName, $varValue) {
            $this->_tpl->assign($varName, $varValue);
        }
        
        public function __get( $sectionName ) {
            if (!isset( $this->_sections[ $sectionName ] ))
                throw new Exception("Invalid section '$sectionName'");
            else return $this->_sections[ $sectionName ];
        }
        
        public function stdout( $buffer ) {
            $this->_stdout .= $buffer;
        }
        
        public function build() {
        
            if ($this->_aborted) {
                echo $this->_cache->store($this->_stdout);
                return;
            }
        
            /* Merge css and javascript dependencies into page */
            
            foreach ( array_keys( $this->_dependencies ) as $dependencyType ) {
                foreach (array_keys( $this->_dependencies[ $dependencyType ] ) as $dependencyLocation) {
                    foreach ( $this->_dependencies[ $dependencyType ][ $dependencyLocation ] as $dependency ) {
                        $this->_tpl->assign("dependency", $dependency );
                        $this->_tpl->parse ('main.dependency_' . $dependencyLocation . '_' . $dependencyType );
                    }
                }
            }
        
            foreach (array_keys( $this->_cfg ) as $cfgKey )
                $this->_tpl->assign( $cfgKey, $this->_cfg[ $cfgKey ] );

            foreach (array_keys( $this->_sections ) as $sectionName )
                $this->_tpl->assign( $sectionName, $this->_sections[ $sectionName ]->__toString() );

            $this->_tpl->parse('main');
            
            echo $this->_cache->store( $this->_tpl->text('main') );
        }
        
        public function addDependency( $dependencyType, $dependencyLocation, $dependency ) {
            if (!isset( $this->_dependencies[ $dependencyType ] ))
                throw new Exception("Invalid dependency type: $dependencyType");
            if (!isset( $this->_dependencies[ $dependencyType ][ $dependencyLocation ] ))
                throw new Exception("Invalid dependency location: $dependencyLocation for dependency type $dependencyType");
            
            if ($dependencyLocation == 'inline' || 
                !in_array( $dependency, $this->_dependencies[ $dependencyType ][ $dependencyLocation ] )
            ) $this->_dependencies[ $dependencyType ][ $dependencyLocation ][] = $dependency;
        }
        
        public function __set( $cfgKey, $cfgValue ) {
            if (!in_array( $cfgKey, array_keys( $this->_cfg ) ) )
                throw new Exception("Invalid class property: $cfgKey");
            $this->_cfg[ $cfgKey ] = $cfgValue;
        }
        
        public function abort() {
            $this->_aborted = TRUE;
        }
    }

?>