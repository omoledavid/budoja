<?php


namespace App\Http\Services;


use App\Models\MenuItem;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Enums\MenuItemStatus;

class MenuItemService
{
    public function allMenuItems($request)
    {
        $q = trim($request->id);
        if ($q) {
            $this->data['menuItems'] = MenuItem::owner()->with('categories')->where('status', MenuItemStatus::ACTIVE)->where('name', 'like', '%' . $q . '%')->orWhere('description', 'like', '%' . $q . '%')->descending()->get();
        } else {
            $this->data['menuItems'] = MenuItem::owner()->with('categories')->where('status', MenuItemStatus::ACTIVE)->descending()->get();
        }

        return $this->data['menuItems'];
    }
    public function show($id){
        return MenuItem::find($id);
    }


    public function store(Request $request)
    {
        $menuItem              = new MenuItem;
        $menuItem->restaurant_id  = $request->get('restaurant_id');
        $menuItem->name           = $request->get('name');
        $menuItem->description    = $request->get('description');
        $menuItem->unit_price     = $request->get('unit_price');
        $menuItem->discount_price = $request->get('discount_price');
        $menuItem->status         = $request->get('status');
        $menuItem->cooking_time         = $request->get('cooking_time');
        $menuItem->save();
        $menuItem->categories()->sync($request->get('categories'));
        
        return $menuItem;
    }
    
    public function media($menuItem)
    {
        if (!blank(request()->file('image'))) {
            $menuItem->media()->delete();
                $menuItem->addMedia(request()->file('image'))->toMediaCollection('menu-items');
        }
    }
    
    public function update(Request $request, $menuItem) : void
    {
        $menuItem->restaurant_id  = $request->get('restaurant_id');
        $menuItem->name           = $request->get('name');
        $menuItem->description    = $request->get('description');
        $menuItem->unit_price     = $request->get('unit_price');
        $menuItem->discount_price = $request->get('discount_price');
        $menuItem->status         = $request->get('status');
        $menuItem->save();
        $menuItem->categories()->sync($request->get('categories'));
    }
    
    public function updateMedia($menuItem)
    {
        if (!blank(request()->file('image'))) {
            $menuItem->media()->delete();
                $menuItem->addMedia(request()->file('image'))->toMediaCollection('menu-items');
        }
    }

    public function delete($menuItem)
    {
        $menuItem->delete();
    }

}
