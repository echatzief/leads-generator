<?php

namespace App\Libraries;

use Illuminate\Support\Facades\Cache;
use MailchimpMarketing\ApiClient;

class Mailchimp
{
    protected ApiClient $client;

    public function __construct()
    {
        $this->client = new ApiClient();

        $this->client->setConfig([
            'apiKey' => env('MAILCHIMP_API_KEY'),
            'server' => env('MAILCHIMP_SERVER'),
        ]);
    }

    public function subscribe(string $email, array $merge_fields)
    {
        $list = $this->findOrCreateList();
        return $this->client->lists->addListMember($list->id, [
            'merge_fields' => $merge_fields,
            "email_address" => $email,
            "status" => "subscribed",
        ]);
    }

    public function unsubscribe(string $email): void
    {
        $list = $this->findOrCreateList();
        $hash = md5(strtolower($email));
        $this->client->lists->deleteListMember($list->id, $hash);
    }

    /**
     * Check the cache for the list, query the Mailchimp API if not found,
     * or create a new list if it doesn't exist
     */
    private function findOrCreateList()
    {
        // Fetch the list from cache if exists
        $list = Cache::get('mailchimp_list');
        if ($list) {
            return $list;
        }

        // If the list doesn't exist at cache fetch it from Mailchimp API
        $list = $this->getListByName(config('mailchimp.name'));
        if ($list) {
            Cache::set('mailchimp_list', $list);
            return $list;
        }

        // If the list doesn't exist create one for our purpose
        $list = $this->createList(
            config('mailchimp.name'),
            config('mailchimp.permission_reminder'),
            config('mailchimp.company'),
            config('mailchimp.address'),
            config('mailchimp.city'),
            config('mailchimp.country'),
            config('mailchimp.from_name'),
            config('mailchimp.from_email'),
            config('mailchimp.subject'),
            config('mailchimp.language'),
        );
        Cache::set('mailchimp_list', $list);

        return $list;
    }

    /**
     * Creates a new list at Mailchimp
     */
    private function createList(
        string $name,
        string $permissionReminder,
        string $company,
        string $address,
        string $city,
        string $country,
        string $fromName,
        string $fromEmail,
        string $subject,
        string $language
    )
    {
        return $this->client->lists->createList([
            "name" => $name,
            "permission_reminder" => $permissionReminder,
            "email_type_option" => true,
            "contact" => [
                "company" => $company,
                "address1" => $address,
                "city" => $city,
                "country" => $country,
            ],
            "campaign_defaults" => [
                "from_name" => $fromName,
                "from_email" => $fromEmail,
                "subject" => $subject,
                "language" => $language,
            ],
        ]);
    }

    /**
     * Returns from all the lists in mailchimp the one with the matching name
     */
    private function getListByName(string $name)
    {
        $lists = $this->getAllLists();
        return collect($lists)->firstWhere('name', $name);
    }

    /**
     * Returns from all the lists in mailchimp the one with the matching id
     */
    private function getListById(string $listId)
    {
        return $this->client->lists->getList($listId);
    }

    /**
     * Returns all the lists that are created at Mailchimp. We use count since the Mailchimp API does
     */
    private function getAllLists(): array
    {
        $lists = [];
        $offset = 0;
        $limit = 1000;
        $hasMoreLists = true;

        while ($hasMoreLists) {
            $response = $this->client->lists->getAllLists(
                '',
                '',
                $limit,
                $offset
            );


            if (!empty($response->lists)) {
                $lists = array_merge($lists, $response->lists);
            }


            $offset += $limit;

            if ($offset >= $response->total_items || empty($response->lists) || count($response->lists) < $limit) {
                $hasMoreLists = false;
            }

            usleep(500000);
        }

        return $lists;
    }
}
