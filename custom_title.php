<?php

/* 
    Copyright (C) 2019  Marvin Scholz

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

class CustomTitlePlugin
{
    private $plugin_id;
    private $page_data;

    private $default_title_default = "%pagetitle% - %sitename%";

    public function __construct()
    {
        // Define and initialize plugin
        register_plugin(
            $this->getPluginId(), // Plugin ID
            'Custom Title', // Plugin name
            '1.5', // Plugin version
            'ePirat', // Plugin author
            'https://epir.at',	// Author website
            'This plugin adds the ability to use a custom title tag.', // Plugin description
            'plugins', // Page type of plugin
            array($this, 'renderSettingsPage') // Function that displays content
        );

        // Initiat Hooks
        add_action('plugins-sidebar', 'createSideMenu', array($this->getPluginId(), 'Custom Title'));
        add_action('edit-extras', array($this, 'renderEditExtras'));
        add_action('changedata-save', array($this, 'saveCustomTitle'));

        add_filter('data_index', array($this, 'hookDataIndex'));
    }

    /**
     * Get the plugin id for this plugin
     */
    private function getPluginId()
    {
        if ($this->plugin_id === null) {
            $this->plugin_id = basename(__FILE__, ".php");
        }
        return $this->plugin_id;
    }

    /**
     * Get the path of the plugin data directory
     * or of a file inside it.
     */
    private function getPluginDataPath($filename = '')
    {
        $folder = GSDATAOTHERPATH . "customtitle/";

        // Ensure folder exists
        file_exists($folder) || mkdir($folder, 0755);

        return $folder . $filename;
    }

    /**
     * Get the default title setting
     */
    private function getDefaultTitleSetting()
    {
        $file = $this->getPluginDataPath('custom.txt');

        return (file_exists($file)) ? file_get_contents($file) : false;
    }

    /**
     * Set the default title setting
     */
    private function setDefaultTitleSetting($title)
    {
        $file = $this->getPluginDataPath('custom.txt');
        $success = file_put_contents($file, $title);
    }

    private function replaceTemplatePlaceholders($title)
    {
        $pdata = $this->page_data;

        // Get parent page name
        $parent_id = $pdata->parent;
        $file = GSDATAPAGESPATH . $parent_id . '.xml';
        if (file_exists($file)) {
            $parent = getXML($file);
            $parent_title = strip_tags(strip_decode($parent->title));
        }

        // Template tags to content mapping
        $replace_mapping = array(
            '%sitename%'    => get_site_name(false),
            '%pagetitle%'   => get_page_clean_title(false),
            '%menutitle%'   => $pdata->menu,
            '%parenttitle%' => $parent_title
        );

        return str_replace(array_keys($replace_mapping), array_values($replace_mapping), $title);
    }

    // -- Public callbacks needed for the plugin --

    public function hookDataIndex($data)
    {
        return $this->page_data = $data;
    }

    public function renderSettingsPage()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['default_title'])) {
                if (empty($_POST['default_title'])) {
                    // Reset to default
                    $this->setDefaultTitleSetting($this->default_title_default);
                } else {
                    $this->setDefaultTitleSetting(safe_strip_decode($_POST['default_title']));
                }
            }
        }
        $default_title = $this->getDefaultTitleSetting();
        if ($default_title === false) {
            $default_title = $this->default_title_default;
        }

        $default_title = htmlspecialchars($default_title, ENT_QUOTES);

        echo <<<HTML
        <h2>Custom Title Settings</h2>
        <h3>Change default title tag:</h3>
        <form method="post" action="">
            <input type="text" name="default_title" size="80" value="{$default_title}"/>
            <input type="submit" name="save" value="Save" />
        </form>
        <br /><br />
        <h3>Information:</h3>
        <p>You can use the following variables:</p>
        <ul>
            <li><code>%sitename%</code> – Name of the website (as defined in GetSimple setup)</li>
            <li><code>%pagetitle%</code> – Title of the current page (as set in the editor)</li>
            <li><code>%parenttitle%</code> – Title of the current pages parent page (if it exists)</li>
            <li><code>%menutitle%</code> – Title of the current page's menu item (if it has one)</li>
        </ul>
        <br />
        <h3>Usage:</h3>
        <p>To activate this plugin, replace in your theme <code>&lt;title&gt;…&lt;/title&gt;</code> with:</p>
        <pre class="unformatted"><code>&lt;title&gt;&lt;?php echo(get_custom_title_tag()); ?&gt;&lt;/title&gt;</code></pre></p>
HTML;
    }

    public function getCustomTitle()
    {
        $title;

        // Check if there is a custom title set
        $pdata = $this->page_data;
        if (isset($pdata->customtitle) && !empty($pdata->customtitle)) {
            $title = $pdata->customtitle;
        } else {
            // Read the default title
            $title = $this->getDefaultTitleSetting();
            if ($title === false) {
                $title = $this->default_title_default;
            }
        }

        return $this->replaceTemplatePlaceholders($title);
    }

    public function renderEditExtras()
    {
        global $data_edit;

        $title = '';
        if (isset($data_edit->customtitle)) {
            $title = $data_edit->customtitle;
        }
        $title = htmlspecialchars($title, ENT_QUOTES);

        echo <<<HTML
        <p>
            <label for="custom-title">Custom page title:</label>
            <input class="text" id="custom-title" type="text" name="customtitle" value="{$title}"/>
        </p>
HTML;
    }

    public function saveCustomTitle()
    {
        global $xml;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        if (isset($_POST['customtitle'])) {
            $note = $xml->addChild('customtitle');
            $note->addCData($_POST['customtitle']);
        }
    }
}

// Initialize plugin
$custom_title_plugin_instance = new CustomTitlePlugin();

// Called by users in templates to insert the tags
function get_custom_title_tag() {
    global $custom_title_plugin_instance;
    return $custom_title_plugin_instance->getCustomTitle();
}
