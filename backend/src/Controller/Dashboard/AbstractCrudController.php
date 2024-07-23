<?php

namespace App\Controller\Dashboard;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractCrudController
{
    public function __construct(
        protected Connection $db,
        protected string $itemName,
        protected string $listRouteName,
        protected string $listTemplateName,
        protected string $editTemplateName,
        protected string $tableName,
        protected string $idField = 'id'
    ) {
    }

    public function listAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $records = $this->db->createQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->fetchAllAssociative();

        return $request->getView()->renderToResponse(
            $response,
            $this->listTemplateName,
            [
                'records' => $records,
            ]
        );
    }

    public function createAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $row = [];
        $error = null;

        if ($request->isPost()) {
            try {
                $row = $this->modifyFromRequest($request);

                unset($row[$this->idField]);

                $this->db->insert($this->tableName, $row);

                $request->getFlash()->success(sprintf('%s created.', $this->itemName));

                return $response->withRedirect(
                    $request->getRouter()->urlFor($this->listRouteName)
                );
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return $request->getView()->renderToResponse(
            $response,
            $this->editTemplateName,
            [
                'isEditMode' => false,
                'row' => $row,
                'error' => $error,
            ]
        );
    }

    public function editAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $id = $this->requireId($request, $params);

        $row = $this->getRecord($id);
        unset($row[$this->idField]);

        $error = null;

        if ($request->isPost()) {
            try {
                $row = $this->modifyFromRequest(
                    $request,
                    $row,
                    true
                );

                $this->db->update($this->tableName, $row, [$this->idField => $id]);

                $request->getFlash()->success(sprintf('%s updated.', $this->itemName));

                return $response->withRedirect(
                    $request->getRouter()->urlFor($this->listRouteName)
                );
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return $request->getView()->renderToResponse(
            $response,
            $this->editTemplateName,
            [
                'isEditMode' => true,
                'row' => $row,
                'error' => $error,
            ]
        );
    }

    protected function requireId(
        ServerRequest $request,
        array $params
    ): int|string {
        $id = $params[$this->idField] ?? $request->getParam($this->idField);
        if (empty($id)) {
            throw new \InvalidArgumentException('ID is required.');
        }

        return $id;
    }

    protected function getRecord(
        int|string $id
    ): array {
        $row = $this->db->createQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->where($this->db->createExpressionBuilder()->eq($this->idField, ':id'))
            ->setParameter('id', $id)
            ->fetchAssociative();

        if ($row === false) {
            throw new \InvalidArgumentException(sprintf('%s not found!', $this->itemName));
        }

        return $row;
    }

    abstract protected function modifyFromRequest(
        ServerRequest $request,
        array $row = [],
        bool $isEditMode = false
    ): array;

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $id = $this->requireId($request, $params);

        $this->db->delete(
            $this->tableName,
            [
                $this->idField => $id,
            ]
        );

        $request->getFlash()->success(sprintf('%s deleted.', $this->itemName));

        return $response->withRedirect(
            $request->getRouter()->urlFor($this->listRouteName)
        );
    }
}
