<?php

namespace App\Repositories;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class RepositoryInterface
 */
interface RepositoryInterface
{
    /**
     * @param array $columns
     * @return $this
     */
    public function columns(array $columns = ['*']);

    /**
     * @param int $limit
     * @return $this
     */
    public function limit($limit = 10);

    /**
     * @param $orderBy
     * @param string $sort
     * @return $this
     */
    public function orderBy($orderBy, $sort = 'DESC');

    /**
     * @param array $data
     * @return mixed
     */
    public function create(array $data);

    /**
     * insert batch with chunk
     * Default chunk = 1000
     *
     * @param array $data
     * @param int $chunk default 1000 record
     */
    public function insertBatchWithChunk(array $data, int $chunk = 1000);

    /**
     * @param $id
     * @return mixed
     */
    public function findOneById($id);

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function findOneBy($key, $value);

    /**
     * @param $key
     * @param $value
     * @return Collection
     */
    public function findManyBy($key, $value);

    /**
     * @param array $ids
     * @return mixed
     */
    public function findManyByIds(array $ids);

    /**
     * @return Collection
     */
    public function findAll();

    /**
     * @param $key
     * @param $value
     * @param int $perPage
     * @return mixed
     */
    public function paginateBy($key, $value, $perPage = 10);

    /**
     * @param int $perPage
     * @return mixed
     */
    public function paginate($perPage = 10);

    /**
     * @param $id
     * @param array $data
     * @return boolean
     */
    public function updateOneById($id, array $data = []);

    /**
     * @param $key
     * @param $value
     * @param array $data
     * @return boolean
     */
    public function updateOneBy($key, $value, array $data = []);

    /**
     * @param $key
     * @param $value
     * @param array $data
     * @return boolean
     */
    public function updateManyBy($key, $value, array $data = []);

    /**
     * @param array $ids
     * @param array $data
     * @return bool
     */
    public function updateManyByIds(array $ids, array $data = []);

    /**
     * @param $id
     * @return boolean
     */
    public function deleteOneById($id);

    /**
     * @param array $ids
     * @return bool
     */
    public function allExist(array $ids);

    /**
     * @param $key
     * @param $value
     * @return boolean
     */
    public function deleteOneBy($key, $value);

    /**
     * @param $key
     * @param $value
     * @return boolean
     */
    public function deleteManyBy($key, $value);


    /**
     * @param array $ids
     * @return mixed
     */
    public function deleteManyByIds(array $ids);

    /**
     * @param array $credentials
     * @return mixed
     */
    public function findOneByCredentials(array $credentials);


    /**
     * @param array $credentials
     * @return mixed
     */
    public function findManyByCredentials(array $credentials);

    /**
     * @param array $credentials
     * @return mixed
     */
    public function findAllByCredentials(array $credentials);

    /**
     * @param $credentials
     * @param int $perPage
     * @return mixed
     */
    public function paginateByCredentials(array $credentials, $perPage = 10);

    /**
     * update one record by credentials
     *
     * @param array $credentials
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function updateOneByCredentials(array $credentials, array $data = []);

    /**
     * update all record by credentials
     *
     * @param array $credentials
     * @param array $data
     * @return mixed
     */
    public function updateAllByCredentials(array $credentials = [], array $data = []);

    /**
     * delete one by credentials
     *
     * @param array $credentials
     * @return boolean
     */
    public function deleteOneByCredentials(array $credentials = []);

    /**
     * delete all by credentials
     * @param $credentials
     * @return bool|null
     * @throws \Exception
     */
    public function deleteAllByCredentials($credentials);

}