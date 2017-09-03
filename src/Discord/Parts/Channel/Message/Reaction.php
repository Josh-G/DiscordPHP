<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel\Message;

use Carbon\Carbon;
use Discord\Helpers\Collection;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Parts\Channel\Channel;
use React\Promise\Deferred;

class Reaction extends Part
{

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'id',
        'channel_id',
        'message_id',
        'user_id',
        'emoji',
        'channel'
    ];

    /**
     * Returns the channel attribute.
     *
     * @return Channel The channel the message was sent in.
     */
    public function getChannelAttribute()
    {
        foreach ($this->discord->guilds as $guild) {
            $channel = $guild->channels->get('id', $this->channel_id);
            if (! empty($channel)) {
                return $channel;
            }
        }

        if ($this->cache->has("pm_channels.{$this->channel_id}")) {
            return $this->cache->get("pm_channels.{$this->channel_id}");
        }

        return $this->factory->create(Channel::class, [
            'id'   => $this->channel_id,
            'type' => Channel::TYPE_DM,
        ], true);
    }

    /**
     * Returns the author attribute.
     *
     * @return Member|User The member that sent the message. Will return a User object if it is a PM.
     */
    public function getAuthorAttribute()
    {
        if ($this->channel->type != Channel::TYPE_TEXT) {
            return $this->factory->create(User::class, $this->attributes['user_id'], true);
        }

        return $this->channel->guild->members->get('id', $this->attributes['user_id']);
    }

    /**
     * Deletes a reaction.
     *
     * @param int    $type     The type of deletion to perform.
     * @param string $emoticon The emoticon to delete (if not all).
     * @param string $id       The user reaction to delete (if not all).
     *
     * @return \React\Promise\Promise
     */
    public function delete()
    {
        $deferred = new Deferred();

        $url = "channels/{$this->channel->id}/messages/{$this->message->id}/reactions/{$this->emoji->name}/{$this->author->id}";

        $this->http->delete(
            $url, []
        )->then(
            \React\Partial\bind_right($this->resolve, $deferred),
            \React\Partial\bind_right($this->reject, $deferred)
        );

        return $deferred->promise();
    }

}
