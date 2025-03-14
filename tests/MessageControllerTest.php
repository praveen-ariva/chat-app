<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Controllers\MessageController;
use App\Models\User;
use App\Models\Group;
use App\Models\Message;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Illuminate\Database\Capsule\Manager as Capsule;

class MessageControllerTest extends TestCase
{
    private $messageController;
    private $capsule;
    private $user;
    private $user2;
    private $group;
    
    protected function setUp(): void
    {
        // Set up the database connection
        $this->capsule = new Capsule;
        $this->capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => ''
        ]);
        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();
        
        // Create the necessary tables
        $this->capsule->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('username')->unique();
            $table->timestamp('created_at')->nullable();
        });
        
        $this->capsule->schema()->create('groups', function ($table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->integer('created_by')->unsigned();
            $table->timestamp('created_at')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
        });
        
        $this->capsule->schema()->create('group_members', function ($table) {
            $table->integer('user_id')->unsigned();
            $table->integer('group_id')->unsigned();
            $table->timestamp('joined_at')->nullable();
            $table->primary(['user_id', 'group_id']);
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('group_id')->references('id')->on('groups');
        });
        
        $this->capsule->schema()->create('messages', function ($table) {
            $table->increments('id');
            $table->integer('group_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->text('content');
            $table->timestamp('created_at')->nullable();
            $table->foreign('group_id')->references('id')->on('groups');
            $table->foreign('user_id')->references('id')->on('users');
        });
        
        // Create test users
        $this->user = User::create([
            'username' => 'testuser',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->user2 = User::create([
            'username' => 'testuser2',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Create test group
        $this->group = Group::create([
            'name' => 'testgroup',
            'created_by' => $this->user->id,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Add users to group
        $this->capsule->table('group_members')->insert([
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
            'joined_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->capsule->table('group_members')->insert([
            'user_id' => $this->user2->id,
            'group_id' => $this->group->id,
            'joined_at' => date('Y-m-d H:i:s')
        ]);
        
        // Initialize controller
        $this->messageController = new MessageController($this->capsule);
    }
    
    protected function tearDown(): void
    {
        // Drop the tables after tests
        $this->capsule->schema()->dropIfExists('messages');
        $this->capsule->schema()->dropIfExists('group_members');
        $this->capsule->schema()->dropIfExists('groups');
        $this->capsule->schema()->dropIfExists('users');
    }
    
    public function testSendMessage()
    {
        // Create mock objects
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        // Set up request mock
        $request->method('getParsedBody')
            ->willReturn([
                'user_id' => $this->user->id,
                'group_id' => $this->group->id,
                'content' => 'Test message'
            ]);
        
        // Set up response mock
        $response->method('withStatus')
            ->with(201)
            ->willReturnSelf();
        $response->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $response->method('getBody')
            ->willReturn($stream);
        
        // Execute the method
        $result = $this->messageController->send($request, $response);
        
        // Verify a message was created
        $message = Message::where('content', 'Test message')->first();
        $this->assertNotNull($message);
        $this->assertEquals('Test message', $message->content);
        $this->assertEquals($this->user->id, $message->user_id);
        $this->assertEquals($this->group->id, $message->group_id);
        
        // Verify the response is correct
        $this->assertSame($response, $result);
    }
    
    public function testSendMessageWithMissingUserId()
    {
        // Create mock objects
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        // Set up request mock
        $request->method('getParsedBody')
            ->willReturn([
                'group_id' => $this->group->id,
                'content' => 'Test message'
                // Missing 'user_id' key
            ]);
        
        // Set up response mock
        $response->method('withStatus')
            ->with(400)
            ->willReturnSelf();
        $response->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $response->method('getBody')
            ->willReturn($stream);
        
        // Execute the method
        $result = $this->messageController->send($request, $response);
        
        // Verify the response is correct
        $this->assertSame($response, $result);
    }
    
    public function testSendMessageAsNonMember()
    {
        // Create a user who is not a member of the group
        $nonMember = User::create([
            'username' => 'nonmember',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Create mock objects
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        // Set up request mock
        $request->method('getParsedBody')
            ->willReturn([
                'user_id' => $nonMember->id,
                'group_id' => $this->group->id,
                'content' => 'Test message'
            ]);
        
        // Set up response mock
        $response->method('withStatus')
            ->with(403)
            ->willReturnSelf();
        $response->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $response->method('getBody')
            ->willReturn($stream);
        
        // Execute the method
        $result = $this->messageController->send($request, $response);
        
        // Verify the response is correct
        $this->assertSame($response, $result);
    }
    
    public function testGetGroupMessages()
    {
        // Add some test messages
        Message::create([
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
            'content' => 'Test message 1',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        Message::create([
            'user_id' => $this->user2->id,
            'group_id' => $this->group->id,
            'content' => 'Test message 2',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Create mock objects
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        // Set up response mock
        $response->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $response->method('getBody')
            ->willReturn($stream);
        
        // Execute the method
        $result = $this->messageController->getByGroup($request, $response, ['id' => $this->group->id]);
        
        // Verify the response is correct
        $this->assertSame($response, $result);
    }
    
    public function testGetMessageFromNonExistingGroup()
    {
        // Create mock objects
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        // Set up response mock
        $response->method('withStatus')
            ->with(404)
            ->willReturnSelf();
        $response->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $response->method('getBody')
            ->willReturn($stream);
        
        // Execute the method
        $result = $this->messageController->getByGroup($request, $response, ['id' => 999]);
        
        // Verify the response is correct
        $this->assertSame($response, $result);
    }
}