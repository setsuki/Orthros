
��������������������������������������
�@�@�@�@�@Orthros
��������������������������������������

��----------------------------��
�@�@�@�@�@�T�v
��----------------------------��
�ȒP�ȃN�G����������OR�}�b�p�ł��B
�@�\�I�ɂ�OR�}�b�p�Ƃ��������N�G���r���_���߂��̂�������܂��񂪁A
�N�G���r���_���͏����@�\���������Ă������Ȃ̂�OR�}�b�p�ƌĂ�ł܂��B





��----------------------------��
�@�@�@�@�@�g����
��----------------------------��
PDO���g���Ă��܂��B
�K�����O�ɃC���X�g�[�����ς܂��Ă��������B

-- �͂��߂�
Orthros.php ��require�Ȃ�include�Ȃ肵�Ă��������B
  require('./orthros/Orthros.php');

-- DB�ɐڑ�
�R���X�g���N�^�ŃR�l�N�V�����܂ōs���܂�
  $db = new Orthros('localhost', 3306, 'test', 'root', 'root');

-- SELECT
  $data = $db->table('test_tbl')
               ->where(array('id' => 10))
               ->select();

-- INSERT
  $db->table('test_tbl')
           ->insert(array('id' => 20, 'score' => 80));

-- DELETE
  $db->table('test_tbl')
           ->where(array('id' => 20))
           ->delete();

-- UPDATE
  $db->table('test_tbl')
           ->where(array('id' => 20))
           ->update(array('score' => 100));

��{�I�ȗ���́A
�e�[�u���w�� �� WHERE�哙���w�� �� �N�G�����s
�Ƃ��������ł��B

��----------------------------��
�@�@�@�@�@���
��----------------------------��
setsuki �Ƃ� yukicon �Ƃ� Yuki Susugi �Ƃ�������Ă܂��������l�ł��B
https://github.com/setsuki
https://twitter.com/yukiconEx



��----------------------------��
�@�@�@�@�@���C�Z���X
��----------------------------��
�C��BSD���C�Z���X�ł��B
���쌠�\���������Ă����΍D���Ɉ����Ă���č\���܂���B
���������ۏ؂ł��B


