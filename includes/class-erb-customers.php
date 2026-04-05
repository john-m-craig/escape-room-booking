<?php if ( ! defined( 'ABSPATH' ) ) exit;
class ERB_Customers {
    public function get_by_email( $email ) { return ERB_DB::get_customer_by_email( $email ); }
    public function get( $id ) { return ERB_DB::get_customer( $id ); }
    public function create( $data ) { return ERB_DB::insert_customer( $data ); }
    public function update( $id, $data ) { ERB_DB::update_customer( $id, $data ); }
    public function verify_password( $customer, $password ) {
        return password_verify( $password, $customer->password_hash );
    }
    public function hash_password( $password ) { return password_hash( $password, PASSWORD_DEFAULT ); }
}
