<?php
function relationship_list_pager($app) {
    $app->setTitle(lang('Relationships'));
    return $app->render(view('relationship::list', array('relationships' => get_family_relationships())));
}
function add_relationship_fam_pager($app) {
    $app->setTitle(lang('Relationship'));
    $message = null;
    $val = input('val');
    if($val) {
        CSRFProtection::validate();
        $validator = validator($val, array(
            'title' => 'required',
            'gender' => 'required'
        ));
        if(validation_passes()) {
            add_relationship_family($val);
            redirect(url_to_pager('admin-relationship-list'));
        } else {
            $message = validation_first();
        }
    }
    return $app->render(view('relationship::add', array('message' => $message)));
}

function edit_relationship_fam_pager($app) {
    $app->setTitle(lang('edit'));
    $message = null;
    $id = input('id');
    $val = input('val');
    $fam = get_family_relationship($id);
    if($fam) {
        if($val) {
            CSRFProtection::validate();
            $validator = validator($val, array(
                'title' => 'required',
                'gender' => 'required'
            ));
            if(validation_passes()) {
                save_relationship_family($val, $fam);
                redirect(url_to_pager('admin-relationship-list'));
            } else {
                $message = validation_first();
            }
        }
    } else {
        $message = 'Relationship does not exist';
    }
    return $app->render(view('relationship::edit', array('message' => $message, 'relationship' => $fam)));
}

function delete_relationship_fam_pager($app) {
    $message = null;
    $id = input('id');
    delete_relationship_fam($id);
    redirect(url_to_pager('admin-relationship-list'));
}
