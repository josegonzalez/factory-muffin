<?php

/*
 * This file is part of Factory Muffin.
 *
 * (c) Graham Campbell <graham@mineuk.com>
 * (c) Scott Robertson <scottymeuk@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * This is eloquent test class.
 *
 * @author Graham Campbell <graham@mineuk.com>
 */
class EloquentTest extends AbstractTestCase
{
    public static function setupBeforeClass()
    {
        $db = new DB();

        $db->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $db->setAsGlobal();
        $db->bootEloquent();

        $db->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        $db->schema()->create('cats', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('user_id');
        });

        parent::setupBeforeClass();

        static::$fm->seed(5, 'User');
        static::$fm->seed(50, 'Cat');
    }

    public function testNumberOfCats()
    {
        $cats = [];
        foreach (User::all() as $user) {
            foreach ($user->cats as $cat) {
                $cats[] = $cat;
            }
        }

        $this->assertCount(50, $cats);
        $this->assertInstanceOf('Cat', $cats[0]);
    }

    public function testNumberOfCatOwners()
    {
        $users = [];
        foreach (Cat::all() as $cat) {
            $users[] = $cat->user;
        }

        $this->assertCount(50, $users);
        $this->assertCount(5, array_unique($users));
        $this->assertInstanceOf('User', $users[0]);
    }

    public function testUserProperties()
    {
        $user = User::first();

        $this->assertGreaterThan(1, strlen($user->name));
        $this->assertGreaterThan(5, strlen($user->email));
        $this->assertContains('@', $user->email);
        $this->assertContains('.', $user->email);
        $this->assertInstanceOf('DateTime', $user->created_at);
        $this->assertInstanceOf('DateTime', $user->updated_at);
        $this->assertSame((string) $user->created_at, (string) $user->updated_at);
        $this->assertFalse($user->xyz == true);
    }

    public function testCatProperties()
    {
        $cat = Cat::first();

        $this->assertGreaterThan(1, strlen($cat->name));
        $this->assertTrue($cat->user_id == true);
        $this->assertFalse($cat->created_at == true);
        $this->assertFalse($cat->updated_at == true);
        $this->assertFalse($cat->xyz == true);
    }

    public function testSavedObjects()
    {
        $reflection = new ReflectionClass(static::$fm);
        $store = $reflection->getProperty('modelStore');
        $store->setAccessible(true);
        $value = $store->getValue(static::$fm);

        $this->assertCount(55, $value->saved());
        $this->assertCount(0, $value->pending());
    }
}

class User extends Eloquent
{
    public $table = 'users';

    public function cats()
    {
        return $this->hasMany('Cat');
    }
}

class Cat extends Eloquent
{
    public $timestamps = false;

    public $table = 'cats';

    public function user()
    {
        return $this->belongsTo('User');
    }
}
