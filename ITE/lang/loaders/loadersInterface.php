<?php
namespace ITE\lang\loaders;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author Fran Díaz <fran.diaz.gonzalez@gmail.com>
 */
interface loadersInterface {
    public function gt($search,...$params);
    public function getText($search,...$params);
}
