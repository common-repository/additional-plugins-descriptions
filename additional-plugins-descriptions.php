<?php
/**
Plugin Name:  Additional Plugins Descriptions
Description:  Allows you to write additional descriptions for plugins.
Version: 0.1.0
Author:       patanaka
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
**/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Can I use //plugin_action_links //after_plugin_row //plugin_row_meta //manage_plugins_custom_column

if (is_admin()) {
    new AdditionalPluginsDescriptions();
}

class AdditionalPluginsDescriptions {
    private $descriptions=array();

    public function __construct()
    {
        //яваскрипт
        add_action('admin_enqueue_scripts',array($this,'action_enqueue_scripts'));
        //выводить доп информацию под стандартным описанием плагина
        add_filter( 'plugin_row_meta', array($this,'filter_plugin_row_meta'), 10, 4 );
        //сохранять изменённое описание плагина
        add_action('wp_ajax_set_plugin_descriptions', array($this,'action_ajax_set_plugin_descriptions'));
        //при удалении любого плагина удалять и временное описание для него
        add_action( 'delete_plugin', array($this,'action_delete_plugin'), 10, 1 );

        //при активации - зарегистрировать хук деактивации
        //при удалении этого плагина - удалить все опции этого плагина
        register_activation_hook( __FILE__, array('AdditionalPluginsDescriptions','action_activation') );

        //todo: может быть удалять описание, только когда плагин уже удалён
        //add_action( 'deleted_plugin',     'action_deleted_plugin', 10, 5 );
    }

    public function action_activation() {
        register_uninstall_hook( __FILE__, array('AdditionalPluginsDescriptions','action_uninstall_me' ));
    }

    public function action_uninstall_me() {
        delete_option('apd_descriptions');
        return;
    }

    public function filter_plugin_row_meta($links, $file, $data, $context ) {
        $this->load_descriptions();

        $plugin_name=$data['Name'];
        $_addtitional_permanent=($plugin_name=='Additional Plugins Descriptions')?'':'<br><i><small>You will SAVE this description even if reinstall plugin</small></i>';
        $_addtitional_temporary=($plugin_name=='Additional Plugins Descriptions')?'':'<br><i><small>You will LOST this description if reinstall plugin</small></i>';

        if (key_exists($plugin_name,$this->descriptions)) {
            if (key_exists('permanent',$this->descriptions[$plugin_name])) {
                $description_permanent=$this->descriptions[$plugin_name]['permanent'];
            }
            if (key_exists('temporary',$this->descriptions[$plugin_name])) {
                $description_temporary=$this->descriptions[$plugin_name]['temporary'];
            }
        };
        if (!isset($description_permanent)) {
            $description_permanent='';
        }
        if (!isset($description_temporary)) {
            $description_temporary='';
        }
        $description_temporary=nl2br($description_temporary);
        $description_permanent=nl2br($description_permanent);

        $description_div=<<<HTML
    
<table style="width: 100%;display: none" class="apd-table" data-plugin_name="$plugin_name">
    <tr>
        <th style="width: 60%;text-align: center;word-break: break-all">
            <b>Temporary description</b>
            $_addtitional_temporary                   
        </th>
        <th style="width: 40%;text-align: center;word-break: break-all">
            <b>Permanent description</b>
            $_addtitional_permanent
        </th>
    </tr>
    <tr>
        <td contenteditable="true" class="apd-editable apd-editable-temporary" style="display:table-cell;word-break: break-all">$description_temporary</td>
        <td contenteditable="true" class="apd-editable apd-editable-permanent" style="display:table-cell;word-break: break-all">$description_permanent</td>
    </tr>
    <tr>
        <td colspan="2" style="text-align: center;display: table-cell">
            Is an additional descriptions of the plugins a useful feature?
            <br>
            If YES, <a style="color:orangered;text-decoration: underline" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=M5PGUKU7FTHAS" target="_blank">please donate</a>.
            
        </td>
    
    </tr>
</table>
HTML;

        return array_merge($links, array($description_div));
    }

    /**
     * ajax
     */
    public function action_ajax_set_plugin_descriptions() {
        //remove wp magic quotes
        $_POST = array_map('stripslashes', $_POST);

        //only <br> tags allowed in description
        $description_temporary=wp_kses(
            trim(strval($_POST['description_temporary'])),
            array('br'=>array())
        );
        $description_permanent=wp_kses(
            trim(strval($_POST['description_permanent'])),
            array('br'=>array())
        );

        //any string as plugin name
        $plugin_name=htmlspecialchars(strval($_POST['plugin_name']));

        $this->load_descriptions();

        $this->descriptions[$plugin_name]['permanent']=$description_permanent;
        $this->descriptions[$plugin_name]['temporary']=$description_temporary;

        $this->save_descriptions();

        wp_die();
    }


    public function action_delete_plugin($file) {
        $file=WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.$file;
        $plugin_data=get_plugin_data( $file, false,false);
        $plugin_name=$plugin_data['Name'];

        $this->load_descriptions();
        if (key_exists($plugin_name,$this->descriptions)) {
            if (key_exists('temporary',$this->descriptions[$plugin_name])) {
                unset($this->descriptions[$plugin_name]['temporary']);
            }
        }
        $this->save_descriptions();
    }

    public function action_enqueue_scripts($hook_suffix) {
        if ($hook_suffix!=='plugins.php')
            return;
        wp_enqueue_script('additionalpluginsdescriptions',plugin_dir_url(__FILE__).'additional-plugins-descriptions.js',['jquery']);
    }

    private function load_descriptions() {
        //todo:скорее всего сериализация не нужна, вп сам сериализует/десериализует
        if (!$this->descriptions) {
            $this->descriptions=get_option('apd_descriptions',array());
            if (!$this->descriptions) {
                add_option('apd_descriptions',array(),'','no');
                $this->descriptions=array();
            } else {
                $this->descriptions=unserialize($this->descriptions);
            }
        }
    }

    private function save_descriptions() {
        update_option('apd_descriptions',serialize($this->descriptions));
    }

}



