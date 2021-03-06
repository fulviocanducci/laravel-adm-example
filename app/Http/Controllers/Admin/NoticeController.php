<?php namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminBaseController;
use App\Http\Requests\NoticeRequest;
use App\Models\Credit;
use App\Models\Notice;
use Illuminate\Http\Request;

class NoticeController extends AdminBaseController
{
    private $repository;

    public function __construct(Notice $repository)
    {
        $this->repository = $repository;
        $this->setItem('title', 'Noticia')
            ->setItemRouteBase('notice');
    }

    public function index(Request $request)
    {
        $entity = $this->repository->orderBy('title');
        if ($filter = $request->get('filter'))
        {
            $entity = $entity->where(function($where) use ($filter){
                $where->where('title', 'LIKE', "%$filter%")
                      ->orWhere('id', $filter);
            });
            $this->setItem('filter', $filter);
        }
        $this->setItem('model', $entity->paginate(10));
        return view($this->getViewNameIndex(), $this->getItems());
    }

    public function create(Credit $credit)
    {
        $this->setItemButtonLabelCreate();
        $this->setItem('modelCredit', $credit->orderBy('name')->pluck('name', 'id'));
        return view($this->getViewNameCreateOrEdit(), $this->getItems());
    }

    public function edit(Credit $credit, $id)
    {
        $this->setItemButtonLabelEdit();
        $entity = $this->repository->find($id);
        if ($entity)
        {
            $this->setItem('model', $entity);
            $this->setItem('modelCredit', $credit->orderBy('name')->pluck('name', 'id'));
            if (!empty($entity->photocover) && file_exists(public_path('photos/notice/'. $entity->photocover)))
            {
                $this->setItem('photocoverimage', '/photos/notice/' . $entity->photocover);
            }
            return view($this->getViewNameCreateOrEdit(), $this->getItems());
        }
        return redirect($this->getRouteBaseIndex());
    }

    public function save(NoticeRequest $request)
    {
        if ($request->get('id'))
        {
            $entity = $this->repository->find($request->get('id'));
            if ($entity)
            {
                $entity->fill($request->only(['title','credit_id','body']));
                $entity->save();
            }
        }
        else
        {
            $entity = $this->repository->create($request->only(['title','credit_id','body']));
        }
        if ($entity)
        {
            if ($request->hasFile('photocover'))
            {
                $photo = $request->file('photocover');
                $photocover = strtolower(($entity->id).'.'.($photo->getClientOriginalExtension()));
                $photo->storeAs('photos/notice', $photocover);
                $entity->photocover = $photocover;
                $entity->save();
            }
            return redirect($this->getRouteBaseEdit($entity->id));
        }
        return redirect($this->getRouteBaseIndex());
    }

    public function delete($id)
    {
        if ($entity = $this->repository->find($id))
        {
            $entity->delete();
        }
        return redirect($this->getRouteBaseIndex());
    }
}
