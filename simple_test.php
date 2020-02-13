<?php

// Разработчика попросили получить данные из REST-API стороннего сервиса. Данные необходимо было кешировать, ошибки логировать.
// Разработчик с задачей справился, ниже предоставлен его код.

// 1. Проведите максимально подробный Code Review. Необходимо написать, с чем вы не согласны и почему.
// 2. Исправьте обозначенные ошибки, предоставив свой вариант кода.

// Resume

// use DateTime; Нет необходимости
// use Exception; Нет необходимости

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class DataProvider
{
    /**
     * А зачем DataProvider'у знать эти данные? Он должен куда-то обратиться и получить либо их, либо полный ответ.
     * KISS как миинимум. И нарушение
     */
    private $host;
    private $user;
    private $password;

    /**
     * @param $host
     * @param $user
     * @param $password
     */
    public function __construct($host, $user, $password)
    {
        /**
         * Нет проверки входных параметреов
         */
        $this->host = $host; // Нет проверки
        $this->user = $user; // Нет проверки
        $this->password = $password; // Нет проверки
    }

    /**
     * @param array $request
     *
     * @return array
     */
    public function get(array $request)
    {
        /**
         * Не описан ни request, ни выход
         */
        // returns a response from external service
    }
}

/**
 * И вообще надо делитть это на классы
 */

// ??
class DecoratorManager extends DataProvider
{
    /**
     * Как было указано ниже, плохая практика. А если кто-то что-то забудет указать? У нас $cache или $logger могуть
     * быть null. В идеале это либо должно браться при инстанциировании класса, либо из какого-то контейнера.
     */
    public $cache;
    public $logger;

    /**
     * @param string $host
     * @param string $user
     * @param string $password
     * @param CacheItemPoolInterface $cache
     */
    public function __construct($host, $user, $password, CacheItemPoolInterface $cache)
    {
        /**
         * ну или тут надо проверять входные параметры
         **/
        parent::__construct($host, $user, $password);

        $this->cache = $cache;
    }

    // ???

    /**
     * Это надо делать через DI или использовать контейнер
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * // ???
     * {@inheritdoc}
     */
    public function getResponse(array $input)
    {
        /**
         * Слишком много в try. Надо разбивать на части.
         */
        try {
            $cacheKey = $this->getCacheKey($input);
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }

            /** Это вообще ужасно. Надо прямым методом к прямому классу в идеале */
            $result = parent::get($input);

            $cacheItem
                ->set($result)
                ->expiresAt(
                    (new DateTime())->modify('+1 day')
                );

            return $result;

        } catch (Exception $e) {
            // Что тут не так
            // Что если мы забыли внедрить зависимость
            $this->logger->critical('Error');
        }

        return [];
    }

    /***
     * ЭЭЭ, стоп. Мы на каком PHP пишем? Если на 5.6, то ок, но если на >7.1, То у нас уже есть исключение там
     */
    public function getCacheKey(array $input)
    {
        return json_encode($input);
    }
}
