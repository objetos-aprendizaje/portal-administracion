<?php

namespace App\Services;

use Exception;

class KafkaService
{

    public function sendMessage($key, $message, $topic)
    {
        $kafkaBroker = env('KAFKA_BROKERS');

        if (!$kafkaBroker) return;

        $producer = new \RdKafka\Producer();
        $producer->setLogLevel(LOG_DEBUG);

        if ($producer->addBrokers($kafkaBroker) < 1) {
            throw new Exception("No se pudo añadir brokers");
        }

        $topic = $producer->newTopic($topic);

        try {
            for ($flushRetries = 0; $flushRetries < 10; $flushRetries++) {
                $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($message), $key);
                $producer->poll(0);

                if ($producer->flush(1000) === RD_KAFKA_RESP_ERR_NO_ERROR) {
                    break;
                }
            }

            if ($flushRetries == 10) throw new Exception("No se pudo enviar el mensaje");
        } catch (Exception $e) {
            throw new Exception("No se pudo enviar el mensaje");
        }
    }

    public function sendMessages($messages)
    {
        $kafkaBroker = env('KAFKA_BROKERS');

        if (!$kafkaBroker) return;

        $producer = new \RdKafka\Producer();
        $producer->setLogLevel(LOG_DEBUG);

        if ($producer->addBrokers($kafkaBroker) < 1) {
            throw new Exception("No se pudo añadir brokers");
        }

        try {
            foreach ($messages as $message) {

                $topic = $producer->newTopic($message['topic']);

                $key = $message['key'];
                $value = $message['value'];
                $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($value), $key);
                $producer->poll(0);
            }

            for ($flushRetries = 0; $flushRetries < 10; $flushRetries++) {
                if ($producer->flush(1000) === RD_KAFKA_RESP_ERR_NO_ERROR) {
                    break;
                }
            }

            if ($flushRetries == 10) {
                throw new Exception("No se pudo enviar los mensajes");
            }
        } catch (Exception $e) {
            throw new Exception("No se pudo enviar los mensajes: " . $e->getMessage());
        }
    }
}
