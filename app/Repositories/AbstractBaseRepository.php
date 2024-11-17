<?php

namespace App\Repositories;

use App\Models\BaseModel;
use Illuminate\Support\Collection;
use App\LibExtension\LogEx;

/**
 * Class AbstractBaseRepository
 * @package App\Repositories
 */
abstract class AbstractBaseRepository implements RepositoryInterface
{
    protected $className = "AbstractBaseRepository";

    protected $model;
    protected $query;
    /**
     * @var array $data
     * query parameters (sort, filters, pagination)
     */
    protected $data;
    protected $columns = ['*'];
    protected $orderBy;
    protected $sortMethod = 'DESC';
    protected $limit = 100000;
    protected $offset = 0;

    public function __construct(BaseModel $model)
    {
        LogEx::constructName($this->className, '__construct');

        $this->model = $model;

        // default order
        $schemaBuilder = $this->model->getConnection()->getSchemaBuilder();
        $this->orderBy = 'id';
    }

    /**
     * array column to get data
     *
     * @param array $columns
     * @return $this
     * @throws \Exception
     */
    public function columns(array $columns = ['*'])
    {
        LogEx::methodName($this->className, 'columns');

        if (is_array($columns) === false) {
            throw new \Exception('');
        }
        $this->columns = $columns;
        return $this;
    }

    /**
     * set limit
     *
     * @param int $limit
     * @return $this
     */
    public function limit($limit = 10)
    {
        LogEx::methodName($this->className, 'limit');

        if (!is_numeric($limit) || $limit < 1) {
            $limit = 10;
        }
        $this->limit = $limit;
        return $this;
    }

    /**
     * set offset
     *
     * @param int $offset
     * @return $this
     * @throws \Exception
     */
    public function offset($offset = 0)
    {
        LogEx::methodName($this->className, 'offset');

        if (!is_numeric($offset) || $offset < 0) {
            throw new \Exception('Offset must be grater than or equal to ZERO');
        }
        $this->offset = $offset;
        return $this;
    }

    /**
     * set order-by
     *
     * @param $orderBy
     * @param string $sort
     * @return $this
     */
    public function orderBy($orderBy = null, $sort = 'DESC')
    {
        LogEx::methodName($this->className, 'orderBy');

        if ($orderBy === null) {
            return $this;
        }
        $this->orderBy = $orderBy;
        if (!in_array(strtoupper($sort), ['DESC', 'ASC'])) {
            $sort = $this->sortMethod;
        }
        $this->sortMethod = $sort;
        return $this;
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        LogEx::methodName($this->className, 'create');

        // Fix enum type for MySQL to postgresql

        if (isset($this->model->enum_mapping)) {
            LogEx::info('model->enum_mapping: ', $this->model->enum_mapping);
            foreach ($this->model->enum_mapping as $colName => $enum_List) {
                if (isset($data[$colName])) {
                    $colVal = $data[$colName];
                    if (is_int($colVal)) {
                        $data[$colName] =  $enum_List[$colVal - 1];
                    }
                }
            }
        }
        return $this->model->create($data);
    }

    /**
     * insert batch with chunk
     * Default chunk = 500
     *
     * @param array $data
     * @param int $chunk default 500 record
     */
    public function insertBatchWithChunk(array $data, int $chunk = 500)
    {
        LogEx::methodName($this->className, 'insertBatchWithChunk');

        $collection = collect($data);
        $chunks = $collection->chunk($chunk);
        foreach ($chunks as $chunk) {
            $this->model->insert($chunk->toArray());
        }
    }

    /**
     * @param $id
     * @return mixed
     */
    public function findOneById($id)
    {
        LogEx::methodName($this->className, 'findOneById');

        return $this->model->find($id, $this->columns);
    }

    /**
     * @param array $ids
     * @return mixed
     */
    public function findManyByIds(array $ids)
    {
        LogEx::methodName($this->className, 'findManyByIds');

        return $this->model
            ->whereIn($this->model->getKeyName(), $ids)
            ->take($this->limit)
            ->skip($this->offset)
            ->get($this->columns);
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function findOneBy($key, $value)
    {
        LogEx::methodName($this->className, 'findOneBy');

        return $this->model
            ->where($key, '=', $value)
            ->orderBy($this->orderBy, $this->sortMethod)
            ->first($this->columns);
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function findOneByNotRaiseException($key, $value)
    {
        LogEx::methodName($this->className, 'findOneByNotRaiseException');

        return $this->model
            ->where($key, '=', $value)
            ->orderBy($this->orderBy, $this->sortMethod)
            ->first($this->columns);
    }

    /**
     * @param $key
     * @param $value
     * @return Collection
     */
    public function findManyBy($key, $value)
    {
        LogEx::methodName($this->className, 'findManyBy');

        return $this->model
            ->where($key, '=', $value)
            ->orderBy($this->orderBy, $this->sortMethod)
            ->take($this->limit)
            ->skip($this->offset)
            ->get($this->columns);
    }

    /**
     * @return Collection
     */
    public function findAll()
    {
        LogEx::methodName($this->className, 'findAll');

        return $this->model
            ->orderBy($this->orderBy, $this->sortMethod)
            ->get($this->columns);
    }

    /**
     * @return Collection
     */
    public function findMany()
    {
        LogEx::methodName($this->className, 'findMany');

        return $this->model
            ->orderBy($this->orderBy, $this->sortMethod)
            ->take($this->limit)
            ->skip($this->offset)
            ->get($this->columns);
    }

    /**
     * @param $key
     * @param $value
     * @param int $perPage
     * @return mixed
     */
    public function paginateBy($key, $value, $perPage = 10)
    {
        LogEx::methodName($this->className, 'paginateBy');

        return $this->model
            ->where($key, '=', $value)
            ->orderBy($this->orderBy, $this->sortMethod)
            ->paginate($perPage, $this->columns);
    }

    /**
     * @param int $perPage
     * @return mixed
     */
    public function paginate($perPage = 10)
    {
        LogEx::methodName($this->className, 'paginate');

        return $this->model
            ->orderBy($this->orderBy, $this->sortMethod)
            ->paginate($perPage, $this->columns);
    }

    /**
     * @param $id
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function updateOneById($id, array $data = [])
    {
        LogEx::methodName($this->className, 'updateOneById');

        if (isset($this->model->enum_mapping)) {
            LogEx::info('model->enum_mapping: ', $this->model->enum_mapping);
            foreach ($this->model->enum_mapping as $colName => $enum_List) {
                if (isset($data[$colName])) {
                    $colVal = $data[$colName];
                    if (is_int($colVal)) {
                        $data[$colName] =  $enum_List[$colVal - 1];
                    }
                }
            }
        }

        if (!is_array($data) || empty($data)) {
            throw new \Exception;
        }

        return $this->model
            ->find($id)
            ->update($data);
    }

    /**
     * @param $key
     * @param $value
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function updateOneBy($key, $value, array $data = [])
    {
        LogEx::methodName($this->className, 'updateOneBy');

        if (!is_array($data) || empty($data)) {
            throw new \Exception;
        }
        return $this->model
            ->where($key, '=', $value)
            ->first()
            ->update($data);
    }

    /**
     * @param $key
     * @param $value
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function updateManyBy($key, $value, array $data = [])
    {
        LogEx::methodName($this->className, 'updateManyBy');

        if (!is_array($data) || empty($data)) {
            throw new \Exception;
        }
        return $this->model
            ->where($key, $value)
            ->take($this->limit)
            ->skip($this->offset)
            ->update($data);
    }

    /**
     * @param array $ids
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function updateManyByIds(array $ids, array $data = [])
    {
        LogEx::methodName($this->className, 'updateManyByIds');

        if (!is_array($data) || empty($data)) {
            throw new \Exception;
        }
        return $this->model
            ->whereIn('id', $ids)
            ->update($data);
    }

    /**
     * @param array $ids
     * @return bool
     */
    public function allExist(array $ids)
    {
        LogEx::methodName($this->className, 'allExist');

        return (count($ids) == $this->model->whereIn('id', $ids)->count());
    }

    /**
     * @param array $credentials
     * @param array $data
     * @return mixed
     */

    /**
     * @param $id
     * @return boolean
     */
    public function deleteOneById($id)
    {
        LogEx::methodName($this->className, 'deleteOneById');

        return $this->model
            ->find($id)
            ->delete();
    }

    /**
     * @param $key
     * @param $value
     * @return boolean
     */
    public function deleteOneBy($key, $value)
    {
        LogEx::methodName($this->className, 'deleteOneBy');

        return $this->model
            ->where($key, '=', $value)
            ->first()
            ->delete();
    }

    /**
     * @param $key
     * @param $value
     * @return boolean
     */
    public function deleteManyBy($key, $value)
    {
        LogEx::methodName($this->className, 'deleteManyBy');

        return $this->model
            ->where($key, $value)
            ->take($this->limit)
            ->skip($this->offset)
            ->delete();
    }

    /**
     * @return mixed
     */
    public function deleteMany()
    {
        LogEx::methodName($this->className, 'deleteMany');

        return $this->model
            ->take($this->limit)
            ->delete();
    }

    /**
     * @param array $ids
     * @return mixed
     */
    public function deleteManyByIds(array $ids)
    {
        LogEx::methodName($this->className, 'deleteManyByIds');

        return $this->model
            ->whereIn('id', $ids)
            ->delete();
    }

    /**
     * @return bool|null
     * @throws \Exception
     */
    public function deleteAll()
    {
        LogEx::methodName($this->className, 'deleteAll');

        return $this->model
            ->delete();
    }

    /////////////////////////////////////
    //////////// credential query ///////
    /////////////////////////////////////

    /**
     * build credentials query
     * array(
     *   'name'     => 'Super Cool',                    <=> AND name = 'Super Cool'
     *   'type'     => ['1', '>']                       <=> AND type > 1
     *   'type1'    => [['1', '>'], ['5', '<']]         <=> AND 1< type1 < 5
     *   'id'       => [['3', '=', 'OR']]               <=> OR id = 3
     * );
     *
     * @param array $credentials
     * @return \App\Models\BaseModel
     */
    protected function buildCredentialsQuery(array $credentials)
    {
        LogEx::methodName($this->className, 'buildCredentialsQuery');

        $results = $this->model;
        if (!empty($credentials)) {
            foreach ($credentials as $key => $_value) {
                $value = $_value;
                $operator = '=';
                if (is_array($_value)) {
                    $value = $_value[0];
                    $operator = isset($_value[1]) ? $_value[1] : $operator;
                    if (is_array($_value[0])) {
                        foreach ($_value as $__value) {
                            $value = $__value[0];
                            $operator = isset($__value[1]) ? $__value[1] : $operator;
                            $hasAndOperator = isset($__value[2]) && (strtolower($__value[2]) != 'and') ? false : true;
                            if ($hasAndOperator) {
                                $results = $results->where($key, $operator, $value);
                            } else {
                                $results = $results->OrWhere($key, $operator, $value);
                            }
                        }
                    } else {
                        $results = $results->where($key, $operator, $value);
                    }
                } else {
                    $results = $results->where($key, $operator, $value);
                }
            }
        }
        return $results;
    }

    /**
     * find one record by
     *
     * @param array $credentials
     * @return mixed
     */
    public function findOneByCredentials(array $credentials)
    {
        LogEx::methodName($this->className, 'findOneByCredentials');

        return $this->buildCredentialsQuery($credentials)
            ->orderBy($this->orderBy, $this->sortMethod)
            ->first($this->columns);
    }

    /**
     * find many by credentials
     *
     * @param array $credentials
     * @return mixed
     */
    public function findManyByCredentials(array $credentials)
    {
        LogEx::methodName($this->className, 'findManyByCredentials');

        return $this->buildCredentialsQuery($credentials)
            ->orderBy($this->orderBy, $this->sortMethod)
            ->take($this->limit)
            ->skip($this->offset)
            ->get($this->columns);
    }

    /**
     * @param array $credentials
     * @return Collection
     */
    public function findAllByCredentials(array $credentials)
    {
        LogEx::methodName($this->className, 'findAllByCredentials');

        return $this->buildCredentialsQuery($credentials)
            ->orderBy($this->orderBy, $this->sortMethod)
            ->get($this->columns);
    }

    /**
     * paginate by credentials
     *
     * @param $credentials
     * @param int $perPage
     * @return mixed
     */
    public function paginateByCredentials(array $credentials, $perPage = 10)
    {
        LogEx::methodName($this->className, 'paginateByCredentials');

        return $this->buildCredentialsQuery($credentials)
            ->orderBy($this->orderBy, $this->sortMethod)
            ->paginate($perPage, $this->columns);
    }

    /**
     * update one record by credentials
     *
     * @param array $credentials
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function updateOneByCredentials(array $credentials, array $data = [])
    {
        LogEx::methodName($this->className, 'updateOneByCredentials');

        if (is_array($data) || empty($data)) {
            throw new \Exception();
        }
        return $this->buildCredentialsQuery($credentials)
            ->first()
            ->update($data);
    }

    /**
     * update all record by credentials
     *
     * @param array $credentials
     * @param array $data
     * @return mixed
     */
    public function updateAllByCredentials(array $credentials = [], array $data = [])
    {
        LogEx::methodName($this->className, 'updateAllByCredentials');

        return $this->buildCredentialsQuery($credentials)
            ->update($data);
    }

    /**
     * delete one by credentials
     *
     * @param array $credentials
     * @return boolean
     */
    public function deleteOneByCredentials(array $credentials = [])
    {
        LogEx::methodName($this->className, 'deleteOneByCredentials');

        return $this->buildCredentialsQuery($credentials)
            ->first()
            ->delete();
    }

    /**
     * delete all by credentials
     * @param $credentials
     * @return bool|null
     * @throws \Exception
     */
    public function deleteAllByCredentials($credentials)
    {
        LogEx::methodName($this->className, 'deleteAllByCredentials');

        return $this->buildCredentialsQuery($credentials)
            ->delete();
    }
}
