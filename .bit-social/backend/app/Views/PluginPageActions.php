<?php

namespace BitApps\Social\Views;

use BitApps\Social\Config;

class PluginPageActions
{
    /**
     * Provides links for plugin pages. Those links will bi displayed in
     * all plugin pages under the plugin name.
     *
     * @return array
     */
    public function getActionLinks()
    {
        return [
            'settings' => [
                'title' => __('Settings', Config::SLUG),
                'url'   => Config::get('ADMIN_URL') . 'admin.php?page=' . Config::SLUG . '#/settings',
            ],
            'support' => [
                'title' => __('Support', Config::SLUG),
                'url'   => Config::get('ADMIN_URL') . 'admin.php?page=' . Config::SLUG . '#/support',
            ],
            // 'offer' => [
            //     'title' => __('BFCM 62% OFF', Config::SLUG),
            //     'url'   => 'https://bit-social.com/special-discount/',
            //     'style' => 'font-weight: bold; color: #6817FF;',
            // ],
        ];
    }

    /**
     *  Render Plugin action links.
     *
     * @param array $links Array of links
     *
     * @return array
     */
    public function renderActionLinks($links)
    {
        $linksToAdd = $this->getActionLinks();

        foreach ($linksToAdd as $key => $link) {
            // Ignore "offer" only when Pro is active
            if (Config::isProActivated() && $key === 'offer') {
                continue;
            }

            $style = !empty($link['style']) ? ' style="' . esc_attr($link['style']) . '"' : '';
            $links[] = '<a href="' . esc_url($link['url']) . '"' . $style . '>' . esc_html($link['title']) . '</a>';
        }

        return $links;
    }
}
