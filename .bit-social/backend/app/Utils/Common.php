<?php

namespace BitApps\Social\Utils;

use BitApps\Social\Config;
use BitApps\Social\HTTP\Controllers\AccountController;

trait Common
{
    public $platformLimitations = [
        'facebook'              => 63206,
        'linkedin'              => 3000,
        'pinterest'             => 497,
        'discord'               => 2000,
        'tumblr'                => 4096,
        'googleBusinessProfile' => 1500,
        'instagram'             => 2200,
        'bluesky'               => 300,
        'line'                  => 5000,
        'telegram'              => 1024,
        'threads'               => 500,
    ];

    /**
     * @param int|array $scheduleIdentifier
     *
     * @return bool|array
     */
    public function removeScheduleHook($scheduleIdentifier)
    {
        if (empty($scheduleIdentifier)) {
            return false;
        }

        if (\is_array($scheduleIdentifier)) {
            $clearedHooks = [];

            foreach ($scheduleIdentifier as $id) {
                $clearedHooks[$id] = $this->clearScheduleById($id);
            }

            return $clearedHooks;
        }

        return $this->clearScheduleById($scheduleIdentifier);
    }

    public function clearScheduleById($id)
    {
        $actionHook = Config::VAR_PREFIX . $id . '_cron_exec';

        return wp_clear_scheduled_hook($actionHook, [['schedule_id' => $id]]);
    }

    public function replacePostContent($postId, $template)
    {
        $link = '';
        $media = [];
        $video = [];

        if (!property_exists($template, 'trimMessage')) {
            $templateOptions = Config::getOption('templates_settings');
            $trimMessage = $templateOptions[$template->platform]['trimMessage'];
            $template->trimMessage = $trimMessage;
        }

        $smartTag = new SmartTag();
        $post = get_post($postId);

        if (!$post) {
            return '';
        }

        $content = $this->replaceTagValue($template->content, $post);
        $comment = isset($template->comment) ? $this->replaceTagValue($template->comment, $post) : null;

        $propertyTagMap = [
            'isFeaturedImage' => SmartTag::tag('featuredImageUrl'),
            'isProductImage'  => SmartTag::tag('productImageUrl'),
            'isLinkCard'      => SmartTag::tag('postLink'),
            'isAllImages'     => 'all_images',
            'isVideo'         => 'video',
            'isPromptImage'   => null,
            'isCustomImage'   => null
        ];

        if (property_exists($template, 'trimMessage') && $template->trimMessage) {
            $content = $this->clipText($template->platform, $content, $link);
            $comment = $this->clipText($template->platform, $comment, '');
        }

        // Handle properties and assign values - consolidate images into media array, video separate
        foreach ($propertyTagMap as $property => $smartTagValue) {
            if ((property_exists($template, $property) && $template->{$property})
                || (property_exists($template, 'postingType') && $template->postingType === $property)) {
                switch ($property) {
                    case 'isFeaturedImage':
                    case 'isProductImage':
                        $mediaValue = $smartTag->getSmartTagValue($smartTagValue, $post);
                        if (!empty($mediaValue)) {
                            $media[] = $mediaValue;
                        }

                        break;
                    case 'isLinkCard':
                        $link = $smartTag->getSmartTagValue($smartTagValue, $post);

                        break;
                    case 'isAllImages':
                        $allImages = $smartTag->getSmartTagValue($smartTagValue, $post);
                        if (!empty($allImages)) {
                            $media = array_merge($media, \is_array($allImages) ? $allImages : [$allImages]);
                        }

                        break;
                    case 'isVideo':
                        $videoValue = $smartTag->getSmartTagValue($smartTagValue, $post);
                        if (!empty($videoValue)) {
                            $video = \is_array($videoValue) ? $videoValue : [$videoValue];
                        }

                        break;
                    case 'isPromptImage':
                        // Get promptImage value from template and resolve it using getSmartTagValue
                        if (property_exists($template, 'promptImage') && !empty($template->promptImage)) {
                            $promptImageTag = $template->promptImage;
                            $promptImageValue = $smartTag->getSmartTagValue($promptImageTag, $post);

                            if (!empty($promptImageValue)) {
                                // If it's a valid URL, add it to media array
                                if (filter_var($promptImageValue, FILTER_VALIDATE_URL)) {
                                    $media[] = $promptImageValue;
                                }
                            }
                        }

                        break;
                    case 'isCustomImage':
                        // Get customImages array from template (array of URLs)
                        if (property_exists($template, 'customImages') && !empty($template->customImages)) {
                            $customImages = $template->customImages;
                            if (\is_array($customImages)) {
                                foreach ($customImages as $customImageUrl) {
                                    if (!empty($customImageUrl) && filter_var($customImageUrl, FILTER_VALIDATE_URL)) {
                                        $media[] = $customImageUrl;
                                    }
                                }
                            }
                        }

                        break;
                }
            }
        }

        // Remove empty and duplicate values from media array
        $media = array_values(array_filter(array_unique($media)));

        $formattedPostData = [
            'content' => $content,
            'media'   => $media,
            'video'   => $video,
            'link'    => $link,
            'comment' => $comment
        ];

        return apply_filters(Config::withPrefix('post_data'), $formattedPostData, $postId);
    }

    public function replaceTagValue($text, $post)
    {
        $pattern = '/\{((?:[^{}]++|(?R))*)\}/';

        $smartTag = new SmartTag();

        preg_match_all($pattern, $text, $matches);

        if (empty($matches[1])) {
            return $text;
        }

        $tags = $matches[1];

        foreach ($tags as $tag) {
            $value = $smartTag->getSmartTagValue($tag, $post);

            // Remove placeholder if no value
            if ($value === '') {
                $text = str_replace('{' . $tag . '} ', '', $text);
            }

            // Replace text tags with the smart tag values
            $text = str_replace('{' . $tag . '}', $value, $text);
        }

        return html_entity_decode($text, ENT_QUOTES);
    }

    public function isPlatFormExists($accountId)
    {
        $accountInfo = AccountController::isExists($accountId);
        if (!$accountInfo) {
            return false;
        }

        $platform = $accountInfo->platform;
        $details = $accountInfo->details;

        $platformDir = 'BitApps\Social\HTTP\Services\Social';

        if (class_exists('BitApps\SocialPro\HTTP\Services\Social' . '\\' . ucfirst($platform) . 'Service' . '\\' . 'PostPublish' . ucfirst($platform) . 'Service') && $platform !== 'facebook' && $platform !== 'linkedin') {
            $platformDir = 'BitApps\SocialPro\HTTP\Services\Social';
        }

        if (!class_exists($platformDir . '\\' . ucfirst($platform) . 'Service' . '\\' . 'PostPublish' . ucfirst($platform) . 'Service')) {
            return false;
        }

        return [
            'class'    => $platformDir . '\\' . ucfirst($platform) . 'Service' . '\\' . 'PostPublish' . ucfirst($platform) . 'Service',
            'platform' => $platform,
            'details'  => \is_string($details) ? json_decode($details) : $details,
        ];
    }

    public function clipText($platform, $content, $link)
    {
        // Ensure $content is always a string
        $content = $content ?? '';

        $backSlashNCount = substr_count($content, "\n");
        $textLength = \strlen($content) - $backSlashNCount;

        $maxLength = $this->platformLimitations[$platform] ?? $textLength;

        if ($textLength > $maxLength) {
            return substr($content, 0, $maxLength);
        }

        return $content;
    }

    public function normalizePostData(array $data, bool $createOthers = false): array
    {
        // Remove media-related keys from allowed list (so they are not returned)
        $allowedKeys = ['content', 'media', 'link', 'comment'];

        $result = [
            'content' => $data['content'] ?? '',
            'media'   => [],
            'link'    => $data['link'] ?? '',
            'comment' => $data['comment'] ?? '',
        ];

        // Merge media
        if (!empty($data['media'])) {
            $result['media'] = \is_array($data['media']) ? $data['media'] : [$data['media']];
        }

        if (!empty($data['featureImage'])) {
            $result['media'][] = $data['featureImage'];
        }

        if (!empty($data['allImages'])) {
            $result['media'] = array_merge(
                $result['media'],
                \is_array($data['allImages']) ? $data['allImages'] : [$data['allImages']]
            );
        }

        if (!empty($data['video'])) {
            $result['media'][] = $data['video'];
        }

        // Remove empty / duplicate values
        $result['media'] = array_values(array_filter(array_unique($result['media'])));

        // Process other data
        foreach ($data as $key => $value) {
            if (!\in_array($key, $allowedKeys, true)
                && !\in_array($key, ['featureImage', 'allImages', 'video'], true)) {
                if ($createOthers) {
                    $result['others'][$key] = $value;
                } else {
                    $result[$key] = $value;
                }
            }
        }

        if ($createOthers && !isset($result['others'])) {
            $result['others'] = [];
        }

        return $result;
    }

    /**
     * Escape characters that could break Markdown/Telegram formatting.
     */
    protected function escapeSpecialCharacters(string $text): string
    {
        $search = ['\\', '|', '{', '}', '(', ')', '<', '>', '#', '*', '_', '~', '[', ']'];
        $replace = ['\\\\', '\|', '\{', '\}', '\(', '\)', '\<', '\>', '\#', '\*', '\_', '\~', '\[', '\]'];

        return str_replace($search, $replace, $text);
    }
}
