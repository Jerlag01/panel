<?php

namespace Pterodactyl\Http\Controllers\Admin\Servers;

use Illuminate\Http\Request;
use Pterodactyl\Models\Nest;
use Pterodactyl\Models\Server;
use Illuminate\Contracts\View\Factory;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Eloquent\NestRepository;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Traits\Controllers\JavascriptInjection;
use Pterodactyl\Repositories\Eloquent\DatabaseHostRepository;

class ServerViewController extends Controller
{
    use JavascriptInjection;

    /**
     * @var \Illuminate\Contracts\View\Factory
     */
    private $view;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\DatabaseHostRepository
     */
    private $databaseHostRepository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    private $repository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\NestRepository
     */
    private $nestRepository;

    /**
     * ServerViewController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\DatabaseHostRepository $databaseHostRepository
     * @param \Pterodactyl\Repositories\Eloquent\NestRepository $nestRepository
     * @param \Pterodactyl\Repositories\Eloquent\ServerRepository $repository
     * @param \Illuminate\Contracts\View\Factory $view
     */
    public function __construct(
        DatabaseHostRepository $databaseHostRepository,
        NestRepository $nestRepository,
        ServerRepository $repository,
        Factory $view
    ) {
        $this->view = $view;
        $this->databaseHostRepository = $databaseHostRepository;
        $this->repository = $repository;
        $this->nestRepository = $nestRepository;
    }

    /**
     * Returns the index view for a server.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request, Server $server)
    {
        return $this->view->make('admin.servers.view.index', compact('server'));
    }

    /**
     * Returns the server details page.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Contracts\View\View
     */
    public function details(Request $request, Server $server)
    {
        return $this->view->make('admin.servers.view.details', compact('server'));
    }

    /**
     * Returns a view of server build settings.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Contracts\View\View
     */
    public function build(Request $request, Server $server)
    {
        $allocations = $server->node->allocations->toBase();

        return $this->view->make('admin.servers.view.build', [
            'server' => $server,
            'assigned' => $allocations->where('server_id', $server->id)->sortBy('port')->sortBy('ip'),
            'unassigned' => $allocations->where('server_id', null)->sortBy('port')->sortBy('ip'),
        ]);
    }

    /**
     * Returns the server startup management page.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Contracts\View\View
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function startup(Request $request, Server $server)
    {
        $parameters = $this->repository->getVariablesWithValues($server->id, true);
        $nests = $this->nestRepository->getWithEggs();

        $this->plainInject([
            'server' => $server,
            'server_variables' => $parameters->data,
            'nests' => $nests->map(function (Nest $item) {
                return array_merge($item->toArray(), [
                    'eggs' => $item->eggs->keyBy('id')->toArray(),
                ]);
            })->keyBy('id'),
        ]);

        return $this->view->make('admin.servers.view.startup', compact('server', 'nests'));
    }

    /**
     * Returns all of the databases that exist for the server.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Contracts\View\View
     */
    public function database(Request $request, Server $server)
    {
        return $this->view->make('admin.servers.view.database', [
            'hosts' => $this->databaseHostRepository->all(),
            'server' => $server,
        ]);
    }

    /**
     * Returns the base server management page, or an exception if the server
     * is in a state that cannot be recovered from.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Contracts\View\View
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function manage(Request $request, Server $server)
    {
        if ($server->installed > 1) {
            throw new DisplayException(
                'This server is in a failed install state and cannot be recovered. Please delete and re-create the server.'
            );
        }

        return $this->view->make('admin.servers.view.manage', compact('server'));
    }

    /**
     * Returns the server deletion page.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Contracts\View\View
     */
    public function delete(Request $request, Server $server)
    {
        return $this->view->make('admin.servers.view.delete', compact('server'));
    }
}
