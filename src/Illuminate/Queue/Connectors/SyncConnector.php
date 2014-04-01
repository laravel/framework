<?php namespace Illuminate\Queue\Connectors; use Illuminate\Queue\SyncQueue; class SyncConnector implements ConnectorInterface { public function connect(array $config) { return new SyncQueue; } }
