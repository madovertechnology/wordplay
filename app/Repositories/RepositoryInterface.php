<?php

namespace App\Repositories;

interface RepositoryInterface
{
    /**
     * Get all records.
     *
     * @param array $columns
     * @return mixed
     */
    public function all(array $columns = ['*']);

    /**
     * Get paginated records.
     *
     * @param int $perPage
     * @param array $columns
     * @return mixed
     */
    public function paginate(int $perPage = 15, array $columns = ['*']);

    /**
     * Find a record by its ID.
     *
     * @param int $id
     * @param array $columns
     * @return mixed
     */
    public function find(int $id, array $columns = ['*']);

    /**
     * Find a record by a specific field.
     *
     * @param string $field
     * @param mixed $value
     * @param array $columns
     * @return mixed
     */
    public function findBy(string $field, $value, array $columns = ['*']);

    /**
     * Create a new record.
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data);

    /**
     * Update a record.
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function update(int $id, array $data);

    /**
     * Delete a record.
     *
     * @param int $id
     * @return mixed
     */
    public function delete(int $id);
}