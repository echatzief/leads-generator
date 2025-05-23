<?php

use App\Libraries\Mailchimp;
use Illuminate\Support\Facades\Cache;
use MailchimpMarketing\ApiClient;

beforeEach(function () {
    $this->apiClientMock = Mockery::mock(ApiClient::class);

    $this->mailchimp = Mockery::mock(Mailchimp::class)->makePartial();

    $reflectionProperty = new ReflectionProperty(Mailchimp::class, 'client');
    $reflectionProperty->setValue($this->mailchimp, $this->apiClientMock);

    Cache::forget('mailchimp_list');
});

afterEach(function () {
    Mockery::close();
});

test('it can subscribe a user to a list', function () {
    $email = 'test@example.com';
    $mergeFields = ['FNAME' => 'John', 'LNAME' => 'Doe'];
    $listId = 'list123';
    $mockList = (object)['id' => $listId];
    $mockResponse = (object)['id' => 'member123', 'email_address' => $email];

    Cache::shouldReceive('get')
        ->once()
        ->with('mailchimp_list')
        ->andReturn($mockList);

    $this->apiClientMock->lists = Mockery::mock();
    $this->apiClientMock->lists->shouldReceive('addListMember')
        ->once()
        ->with($listId, [
            'merge_fields' => $mergeFields,
            'email_address' => $email,
            'status' => 'subscribed',
        ])
        ->andReturn($mockResponse);

    $result = $this->mailchimp->subscribe($email, $mergeFields);

    expect($result)
        ->toBe($mockResponse)
        ->and($result->email_address)->toBe($email);
});

test('it can unsubscribe a user from a list', function () {
    $email = 'test@example.com';
    $emailHash = md5(strtolower($email));
    $listId = 'list123';
    $mockList = (object)['id' => $listId];

    Cache::shouldReceive('get')
        ->once()
        ->with('mailchimp_list')
        ->andReturn($mockList);

    $this->apiClientMock->lists = Mockery::mock();
    $this->apiClientMock->lists->shouldReceive('deleteListMember')
        ->once()
        ->with($listId, $emailHash)
        ->andReturnNull();

    $this->mailchimp->unsubscribe($email);
});

test('it fetches list from cache when available', function () {
    $email = 'test@example.com';
    $mergeFields = ['FNAME' => 'John', 'LNAME' => 'Doe'];
    $listId = 'cached_list_id';
    $mockList = (object)['id' => $listId];
    $mockResponse = (object)['id' => 'member123', 'email_address' => $email];

    Cache::shouldReceive('get')
        ->once()
        ->with('mailchimp_list')
        ->andReturn($mockList);

    $this->apiClientMock->lists = Mockery::mock();
    $this->apiClientMock->lists->shouldReceive('addListMember')
        ->once()
        ->with($listId, [
            'merge_fields' => $mergeFields,
            'email_address' => $email,
            'status' => 'subscribed',
        ])
        ->andReturn($mockResponse);
    $this->apiClientMock->lists->shouldNotReceive('getAllLists');
    $this->apiClientMock->lists->shouldNotReceive('createList');

    $result = $this->mailchimp->subscribe($email, $mergeFields);

    expect($result)
        ->toBe($mockResponse)
        ->and($result->email_address)->toBe($email);
});
