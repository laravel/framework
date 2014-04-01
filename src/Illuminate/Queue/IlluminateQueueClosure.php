<?php class IlluminateQueueClosure { public function fire($job, $data) { $closure = unserialize($data['closure']); $closure($job); } }
